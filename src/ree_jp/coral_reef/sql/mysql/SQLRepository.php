<?php
/*
 *  CCCCC                        lll RRRRRR                 fff
 * CC    C  oooo  rr rr    aa aa lll RR   RR   eee    eee  ff
 * CC      oo  oo rrr  r  aa aaa lll RRRRRR  ee   e ee   e ffff
 * CC    C oo  oo rr     aa  aaa lll RR  RR  eeeee  eeeee  ff
 *  CCCCC   oooo  rr      aaa aa lll RR   RR  eeeee  eeeee ff
 *
 * Copyright (c) 2021-2022. Ree-jp(https://ree-jp.net)
 */

namespace ree_jp\coral_reef\sql\mysql;

use Closure;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use poggit\libasynql\SqlError;
use ree_jp\coral_reef\account\AccountService;
use ree_jp\coral_reef\account\AccountStore;
use ree_jp\coral_reef\account\UserAccount;
use ree_jp\coral_reef\CoralReefPlugin;
use ree_jp\coral_reef\land\LandData;
use ree_jp\coral_reef\money\MoneyCache;
use ree_jp\coral_reef\session\SessionData;
use ree_jp\coral_reef\sql\Repository;
use ree_jp\coral_reef\sql\RepositoryPool;
use ree_jp\coral_reef\sql\SQLConst;
use ree_jp\reef_edge\ReefEdgePlugin;
use ree_jp\reef_edge\socket\SocketService;
use SOFe\AwaitGenerator\Await;

class SQLRepository implements Repository
{
    public array $setting = [];
    public string $server;

    public function __construct(private RepositoryPool $pool, private AccountStore $accountStore, bool $isInit)
    {
        if ($isInit) {
            $this->createFunction();
            $this->createTable();

            // サーバーアカウントを作成(初期スポーンの保護などに使う)
            $this->setUser("0", TextFormat::GREEN . "Reef " . TextFormat::YELLOW . "Server" . TextFormat::RESET, "0.0.0.0");
        }
    }

    public function close(): void
    {
        MoneyCache::purgeAll($this);
    }

    public function loadUser(Player $p): void
    {
        $xuid = $p->getXuid();

        // ユーザーデータを読み込む
        $this->accountStore->setValue($xuid, "wait_action");
        $await = [];
        $await[] = Await::promise(fn($func) => $this->pool->getConnection()->executeSelect('coral_reef.user.get', ['xuid' => intval($p->getXuid())],
            function (array $rows) use ($func, $p) {
                if (!$p->isOnline()) return;

                $xuid = $p->getXuid();
                $name = $p->getName();
                $arrayAccount = array_shift($rows);
                if (isset($arrayAccount['xuid']) && isset($arrayAccount['name']) && isset($arrayAccount['experience'])) {
                    $skill = $arrayAccount['skill'] ?? null;
                    $account = new UserAccount($arrayAccount['xuid'], $arrayAccount['name'], intval($arrayAccount['experience']), $skill);
                    $this->accountStore->users[$account->xuid] = $account;
                } elseif (empty($arrayAccount)) { // データが存在しないとき新しくデータを作る
                    $this->accountStore->users[$xuid] = new UserAccount($xuid, $name, 0, null);
                    SocketService::sendBroadcastMessage(ReefEdgePlugin::$socketClient, TextFormat::AQUA . $name . "さんが初めてサーバーにログインしました");
                } else { // データ壊れてるよ
                    Server::getInstance()->getLogger()->warning($xuid . 'のデータの読み込みに失敗しました');
                    $p->kick("\n§cデータの読み込みに失敗しました");
                    return;
                }
                $func();
            }));
        $await[] = Await::promise(fn($func) => $this->getAllSubtypeValue($xuid, SQLConst::TYPE_SETTINGS,
            function (array $rows) use ($func, $xuid) {
                foreach ($rows as $option) {
                    if (array_key_exists("subtype", $option) && array_key_exists("value", $option)) {
                        $this->setting[$xuid][$option["subtype"]] = $option["value"];
                    } else {
                        Server::getInstance()->getLogger()->warning($xuid . "の設定の読み込みに失敗しました");
                    }
                }
                $func();
            }));
        $await[] = AccountService::loadPlayerData($this->pool, $p);
        Await::all($await);
        if (!$p->isConnected()) return;
        $p->sendMessage("データを読み込みました");
        // クライアント側の準備が整ったのにデータを読み込めてなかったら動けなくしているため解除する
        $this->accountStore->setValue($xuid, "wait_action", 0);
        $p->setImmobile(false);
    }

    public function getAllUser(Closure $func): void
    {
        $this->pool->getConnection()->executeSelect("coral_reef.user.all", [], $func);
    }

    public function setUser(string $xuid, string $name, string $ip): void
    {
        $this->pool->getConnection()->executeSelect('coral_reef.user.get_ip', ['xuid' => intval($xuid)], function (array $rows) use ($ip, $name, $xuid) {
            // 新しいipアドレスからのログインだったら記録する
            $row = array_shift($rows);
            $ips = [];
            if (isset($row['ips'])) {
                $ips = explode(':', $row['ips']);
            }
            if (!in_array($ip, $ips)) $ips[] = $ip;
            $this->pool->getConnection()->executeInsert('coral_reef.user.set.account',
                ['xuid' => intval($xuid), 'name' => $name, 'ips' => implode(':', $ips)], null,
                function (SqlError $error) use ($name) {
                    Server::getInstance()->getLogger()->error("[SQL] $name のデータ保存中に" . $error->getErrorMessage());
                });
        }, function (SqlError $error) use ($name) {
            Server::getInstance()->getLogger()->error("[SQL] $name のip取得中に" . $error->getErrorMessage());
        });
    }

    public function setXp(string $xuid, string $experience, ?Closure $func): void
    {
        $this->pool->getConnection()->executeGeneric('coral_reef.user.set.xp', ['xuid' => intval($xuid), 'experience' => intval($experience)], $func,
            function (SqlError $error) use ($xuid) {
                Server::getInstance()->getLogger()->error("[SQL] $xuid のxp保存中に" . $error->getErrorMessage());
            });
    }

    public function setSkill(string $xuid, ?string $skill, ?Closure $func): void
    {
        $this->pool->getConnection()->executeGeneric('coral_reef.user.set.skill', ['xuid' => intval($xuid), 'skill' => $skill], $func,
            function (SqlError $error) use ($xuid) {
                Server::getInstance()->getLogger()->error("[SQL] $xuid のスキル保存中に" . $error->getErrorMessage());
            });
    }

    public function getMoney(string $xuid, ?Closure $func, ?Closure $failure = null): void
    {
        $this->pool->getConnection()->executeSelect('coral_reef.money.get', ['xuid' => intval($xuid)], $func, $failure);
    }

    public function addMoney(string $xuid, int $money, ?Closure $func, ?Closure $failure = null): void
    {
        $this->pool->getConnection()->executeInsert('coral_reef.money.add', ["xuid" => intval($xuid), "money" => $money], $func, $failure);
    }

    public function getValue(string $xuid, string $type, string $subtype, ?Closure $func, ?Closure $failure = null): void
    {
        $this->pool->getConnection()->executeSelect('coral_reef.values.get.one', ['xuid' => intval($xuid), 'type' => strtolower($type), 'subtype' => strtolower($subtype)],
            $func, $failure);
    }

    public function getAllSubtypeValue(string $xuid, string $type, ?Closure $func, ?Closure $failure = null): void
    {
        $this->pool->getConnection()->executeSelect('coral_reef.values.get.all_subtype', ['xuid' => $xuid, 'type' => strtolower($type)],
            $func, $failure);
    }

    public function getAllUserSubtypeValue(string $type, ?Closure $func, ?Closure $failure = null): void
    {
        $this->pool->getConnection()->executeSelect("coral_reef.values.get.all_user_subtype", ["type" => strtolower($type)],
            $func, $failure);
    }

    public function setValue(string $xuid, string $type, string $subtype, ?string $value, ?Closure $func, ?Closure $failure = null): void
    {
        $this->pool->getConnection()->executeInsert('coral_reef.values.set',
            ['xuid' => intval($xuid), 'type' => strtolower($type), 'subtype' => strtolower($subtype), 'value' => $value], $func, $failure);
    }

    public function addValue(string $xuid, string $type, string $subtype, int $value, ?Closure $func, ?Closure $failure = null): void
    {
        $this->pool->getConnection()->executeInsert('coral_reef.values.add',
            ['xuid' => intval($xuid), 'type' => strtolower($type), 'subtype' => strtolower($subtype), 'value' => $value], $func, $failure);
    }

    public function deleteValue(string $xuid, string $type, string $subtype, ?Closure $func, ?Closure $failure = null): void
    {
        $this->pool->getConnection()->executeGeneric('coral_reef.values.delete', ['xuid' => intval($xuid), 'type' => strtolower($type), 'subtype' => strtolower($subtype)],
            $func, $failure);
    }

    public function getWarps(string $xuid, ?Closure $func): void
    {
        $this->pool->getConnection()->executeSelect('coral_reef.warp.get', ['xuid' => intval($xuid), 'server' => CoralReefPlugin::$serverID],
            $func, $this->noticeByXUid($xuid, '§c >> エラーが発生しました'));
    }

    public function addWarp(string $xuid, string $name, string $level, int $x, int $y, int $z): void
    {
        $this->pool->getConnection()->executeInsert('coral_reef.warp.create',
            ['xuid' => intval($xuid), 'name' => $name, 'server' => CoralReefPlugin::$serverID, 'level' => $level, 'x' => $x, 'y' => $y, 'z' => $z],
            $this->noticeByXUid($xuid, '§a >> ワープ地点を作成しました'), $this->noticeByXUid($xuid, '§c >> エラーが発生しました'));
    }

    public function deleteWarp(string $xuid, string $name): void
    {
        $this->pool->getConnection()->executeGeneric('coral_reef.warp.delete', ['xuid' => intval($xuid), 'name' => $name, 'server' => CoralReefPlugin::$serverID],
            $this->noticeByXUid($xuid, '§a >> ワープ地点を削除しました'), $this->noticeByXUid($xuid, '§c >> エラーが発生しました'));
    }

    public function loadProtectLand(Closure $func, Closure $failure): void
    {
        $this->pool->getConnection()->executeSelect('coral_reef.land.get', ['server' => CoralReefPlugin::$serverID], $func, $failure);
    }

    public function addProtectLand(LandData $land, Closure $func, Closure $failure): void
    {
        $this->pool->getConnection()->executeInsert('coral_reef.land.create', ['xuid' => intval($land->xuid), 'name' => $land->name, 'server' => CoralReefPlugin::$serverID,
            'level' => $land->level, 'mx' => $land->aabb->maxX, 'sx' => $land->aabb->minX, 'mz' => $land->aabb->maxZ, 'sz' => $land->aabb->minZ],
            $func, $failure);
    }

    public function deleteProtectLand(LandData $land, Closure $func, Closure $failure): void
    {
        $this->pool->getConnection()->executeGeneric('coral_reef.land.delete', ['xuid' => intval($land->xuid), 'name' => $land->name, 'server' => CoralReefPlugin::$serverID],
            $func, $failure);
    }

    public function addLog(string $xuid, string $type, ?string $subType, ?string $value, ?string $time, ?Closure $func, ?Closure $failure): void
    {
        if ($time === 'now') $time = date(SQLConst::DATE_FORMAT);
        $this->pool->getConnection()->executeInsert('coral_reef.log.add', ['xuid' => intval($xuid), 'type' => $type, 'subtype' => $subType, 'value' => $value,
            'time' => $time], $func, $failure);
    }

    public function getLog(string $xuid, string $type, Closure $func, Closure $failure): void
    {
        $this->pool->getConnection()->executeSelect("coral_reef.log.get.type_sort_newest", ["xuid" => intval($xuid), "type" => $type], $func, $failure);
    }

    public function recordSession(string $xuid, SessionData $session): void
    {
        $this->pool->getConnection()->executeGeneric("coral_reef.session.add", ["xuid" => $xuid, "server" => CoralReefPlugin::$serverID,
            "join_time" => date(SQLConst::DATE_FORMAT, $session->joinTime), "quit_time" => date(SQLConst::DATE_FORMAT, $session->quitTime),
            "break_count" => $session->breakCount, "place_count" => $session->placeCount, "skill_count" => $session->skillCount]);
    }

    public function getRecentSession(string $xuid, Closure $func, ?Closure $failure): void
    {
        $this->pool->getConnection()->executeSelect("coral_reef.session.get_recent", ["xuid" => $xuid, "server" => CoralReefPlugin::$serverID], $func, $failure);
    }

    public function getAllCountWithQuit(int $firstTime, int $lastTime, Closure $func, ?Closure $failure): void
    {
        $this->pool->getConnection()->executeSelect("coral_reef.session.all_get_count_quit_between_sort_desc", ["first_time" => date(SQLConst::DATE_FORMAT, $firstTime),
            "last_time" => date(SQLConst::DATE_FORMAT, $lastTime)], $func, $failure);
    }

    private function noticeByXUid(string $xuid, string $notice): Closure
    {
        return function () use ($notice, $xuid) {
            foreach (Server::getInstance()->getOnlinePlayers() as $p) {
                if ($p->getXuid() === $xuid) $p->sendMessage($notice);
            }
        };
    }

    private function createFunction(): void
    {
        $this->pool->getConnection()->executeGeneric("coral_reef.init.functions.add_value.reset");
        $this->pool->getConnection()->executeGeneric("coral_reef.init.functions.add_value.create");
    }

    private function createTable(): void
    {
        $this->pool->getConnection()->executeGeneric('coral_reef.init.tables.user');
        $this->pool->getConnection()->executeGeneric('coral_reef.init.tables.ban');
        $this->pool->getConnection()->executeGeneric('coral_reef.init.tables.money');
        $this->pool->getConnection()->executeGeneric('coral_reef.init.tables.warp');
        $this->pool->getConnection()->executeGeneric('coral_reef.init.tables.land');
        $this->pool->getConnection()->executeGeneric('coral_reef.init.tables.virtual_value');
        $this->pool->getConnection()->executeGeneric('coral_reef.init.tables.log');
        $this->pool->getConnection()->executeGeneric("coral_reef.init.tables.session");
    }
}
