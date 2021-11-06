<?php
/*
 *  CCCCC                        lll RRRRRR                 fff
 * CC    C  oooo  rr rr    aa aa lll RR   RR   eee    eee  ff
 * CC      oo  oo rrr  r  aa aaa lll RRRRRR  ee   e ee   e ffff
 * CC    C oo  oo rr     aa  aaa lll RR  RR  eeeee  eeeee  ff
 *  CCCCC   oooo  rr      aaa aa lll RR   RR  eeeee  eeeee ff
 *
 * Copyright (c) 2021-2021. Ree-jp(https://ree-jp.net)
 */

namespace ree_jp\coral_reef\sql;


use Closure;
use PDOException;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;
use poggit\libasynql\DataConnector;
use poggit\libasynql\libasynql;
use poggit\libasynql\SqlError;
use ree_jp\coral_reef\account\AccountManager;
use ree_jp\coral_reef\account\UserAccount;
use ree_jp\coral_reef\CoralReefPlugin;
use ree_jp\coral_reef\land\LandData;
use ree_jp\coral_reef\land\LandManager;

class SQLManager
{
    static ?SQLManager $manager = null;

    private DataConnector $db;

    public array $users = [];
    public array $ban = [];
    public array $setting = [];
    private string $server;

    /**
     * @throws PDOException
     */
    public function __construct(string $path, string $server)
    {
        $config = new Config($path . 'sql.yml');
        Server::getInstance()->getLogger()->info('[SQL] サーバーに接続中...');
        $this->db = libasynql::create(CoralReefPlugin::$plugin, $config->get('database'), [
            "mysql" => "mysql.sql",
        ]);
        Server::getInstance()->getLogger()->info('[SQL] 準備しています');
        $this->createTable();

        // BANデータを用意しとく
        $this->loadBan();
        // サーバーアカウントを作成(初期スポーンの保護などに使う)
        $this->setUser('0', TextFormat::GREEN . 'Reef ' . TextFormat::YELLOW . 'Server' . TextFormat::RESET, '0.0.0.0');

        $this->db->waitAll();
        Server::getInstance()->getLogger()->info('[SQL] complete');
        $this->server = $server;
    }

    public function close(): void
    {
        Server::getInstance()->getLogger()->info('[SQL] クエリの終了を待っています');
        $this->db->waitAll();
        $this->db->close();
        Server::getInstance()->getLogger()->info('[SQL] complete');
    }

    public function loadBan(): void
    {
        $this->db->executeSelect('coral_reef.ban.get', [], function (array $rows) {
            $this->ban = $rows;
        }, function (SqlError $error) {
            CoralReefPlugin::$plugin->setError('BAN情報を読み込み中に' . $error->getErrorMessage());
        });
    }

    /**
     * Banされている時はその理由、されていないときはnull
     */
    public function getBanReason(string $xuid, string $ip): ?string
    {
        foreach ($this->ban as $ban) {
            switch ($ban['type']) {
                case 'XUID':
                    if ($xuid === $ban['value']) return $ban['reason'];
                    break;
                case 'IP':
                    if ($ip === $ban['value']) return $ban['reason'];
                    break;
            }
        }
        return null;
    }

    public function loadUser(string $xuid, string $name): void
    {
        // ユーザーデータを読み込む
        $this->db->executeSelect('coral_reef.user.get', ['xuid' => intval($xuid)], function (array $rows) use ($name, $xuid) {
            $arrayAccount = array_shift($rows);
            if (isset($arrayAccount['xuid']) && isset($arrayAccount['name']) && isset($arrayAccount['experience'])) {
                $skill = $arrayAccount['skill'] ?? null;
                $account = new UserAccount($arrayAccount['xuid'], $arrayAccount['name'], intval($arrayAccount['experience']), $skill);
                $this->users[$account->xuid] = $account;
            } elseif (empty($arrayAccount)) { // データが存在しないとき新しくデータを作る
                $this->users[$xuid] = new UserAccount($xuid, $name, 0, null);
            } else { // データ壊れてるよ
                Server::getInstance()->getLogger()->warning($xuid . 'のデータの読み込みに失敗しました');
                return;
            }
            $p = Server::getInstance()->getPlayer($name);
            if (is_null($p)) return;
            $p->sendMessage('データを読み込みました');
            // クライアント側の準備が整ったのにデータを読み込めてなかったら動けなくしているため解除する
            if (AccountManager::hasValue($xuid, 'wait_action')) {
                AccountManager::setValue($xuid, 'wait_action', 0);
                $p->setImmobile(false);
            }
        });
        $this->getAllSubtypeValue($xuid, SQLConst::TYPE_SETTINGS, function (array $rows) use ($xuid) {
            foreach ($rows as $option) {
                if (array_key_exists('subtype', $option) && array_key_exists('value', $option)) {
                    $this->setting[$xuid][$option['subtype']] = $option['value'];
                } elseif (!empty($rows)) {
                    Server::getInstance()->getLogger()->warning($xuid . 'の設定の読み込みに失敗しました');
                }
            }
        });
    }

    public function getAllUser(Closure $func): void
    {
        $this->db->executeSelect("coral_reef.user.all", [], $func);
    }

    public function getUser(string $xuid): ?UserAccount // 今サーバーに参加してるプレイヤーのみ取得できる
    {
        if (array_key_exists($xuid, $this->users)) return $this->users[$xuid];
        return null;
    }

    public function setUser(string $xuid, string $name, string $ip): void
    {
        $this->db->executeSelect('coral_reef.user.get_ip', ['xuid' => intval($xuid)], function (array $rows) use ($ip, $name, $xuid) {
            // 新しいipアドレスからのログインだったら記録する
            $ips = [];
            if (isset($rows['ips'])) {
                $ips = explode(':', $rows['ips']);
            }
            if (!in_array($ip, $ips)) array_push($ips, $ip);
            $this->db->executeInsert('coral_reef.user.set.account',
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
        $this->db->executeGeneric('coral_reef.user.set.xp', ['xuid' => intval($xuid), 'experience' => intval($experience)], $func,
            function (SqlError $error) use ($xuid) {
                Server::getInstance()->getLogger()->error("[SQL] $xuid のxp保存中に" . $error->getErrorMessage());
            });
    }

    public function setSkill(string $xuid, ?string $skill, ?Closure $func): void
    {
        $this->db->executeGeneric('coral_reef.user.set.skill', ['xuid' => intval($xuid), 'skill' => $skill], $func,
            function (SqlError $error) use ($xuid) {
                Server::getInstance()->getLogger()->error("[SQL] $xuid のスキル保存中に" . $error->getErrorMessage());
            });
    }

    public function getValue(string $xuid, string $type, string $subtype, ?Closure $func, ?Closure $failure = null): void
    {
        $this->db->executeSelect('coral_reef.values.get.one', ['xuid' => intval($xuid), 'type' => strtolower($type), 'subtype' => strtolower($subtype)],
            $func, $failure);
    }

    public function getAllSubtypeValue(string $xuid, string $type, ?Closure $func, ?Closure $failure = null): void
    {
        $this->db->executeSelect('coral_reef.values.get.all_subtype', ['xuid' => $xuid, 'type' => strtolower($type)],
            $func, $failure);
    }

    public function setValue(string $xuid, string $type, string $subtype, ?string $value, ?Closure $func, ?Closure $failure = null): void
    {
        $this->db->executeInsert('coral_reef.values.set',
            ['xuid' => intval($xuid), 'type' => strtolower($type), 'subtype' => strtolower($subtype), 'value' => $value], $func, $failure);
    }

    public function deleteValue(string $xuid, string $type, string $subtype, ?Closure $func, ?Closure $failure = null): void
    {
        $this->db->executeGeneric('coral_reef.values.delete', ['xuid' => intval($xuid), 'type' => strtolower($type), 'subtype' => strtolower($subtype)],
            $func, $failure);
    }

    public function getWarps(string $xuid, ?Closure $func): void
    {
        $this->db->executeSelect('coral_reef.warp.get', ['xuid' => intval($xuid), 'server' => $this->server],
            $func, $this->noticeByXUid($xuid, 'エラーが発生しました'));
    }

    public function addWarp(string $xuid, string $name, string $level, int $x, int $y, int $z): void
    {
        $this->db->executeInsert('coral_reef.warp.create',
            ['xuid' => intval($xuid), 'name' => $name, 'server' => $this->server, 'level' => $level, 'x' => $x, 'y' => $y, 'z' => $z],
            $this->noticeByXUid($xuid, 'ワード地点を作成しました'), $this->noticeByXUid($xuid, 'エラーが発生しました'));
    }

    public function deleteWarp(string $xuid, string $name): void
    {
        $this->db->executeGeneric('coral_reef.warp.delete', ['xuid' => intval($xuid), 'name' => $name, 'server' => $this->server],
            $this->noticeByXUid($xuid, 'ワード地点を削除しました'), $this->noticeByXUid($xuid, 'エラーが発生しました'));
    }

    public function loadProtectLand(Closure $func, Closure $failure): void
    {
        $this->db->executeSelect('coral_reef.land.get', ['server' => $this->server], $func, $failure);
    }

    public function addProtectLand(LandData $land, Player $p): void
    {
        $this->db->executeInsert('coral_reef.land.create', ['xuid' => intval($land->xuid), 'name' => $land->name, 'server' => $this->server,
            'level' => $land->level, 'mx' => $land->aabb->maxX, 'sx' => $land->aabb->minX, 'mz' => $land->aabb->maxZ, 'sz' => $land->aabb->minZ],
            function (int $insertId, int $affectedRows) use ($p, $land) {
                array_push(LandManager::$instance->lands, $land);
                $p->sendMessage($land->name . 'を作成しました');
            }, function (SqlError $error) use ($p, $land) {
                Server::getInstance()->getLogger()->error("[LandSQL] $land->name の作成中に" . $error->getErrorMessage());
                $p->sendMessage('エラーが発生しました');
            });
    }

    public function deleteProtectLand(LandData $land, Player $p): void
    {
        $this->db->executeGeneric('coral_reef.land.delete', ['xuid' => intval($land->xuid), 'name' => $land->name, 'server' => $this->server],
            function () use ($p, $land) {
                foreach (LandManager::$instance->lands as $key => $cacheLand) {
                    if ($cacheLand->xuid === $land->xuid && $cacheLand->name === $land->name) {
                        array_splice(LandManager::$instance->lands, $key, 1);
                    }
                }
                $p->sendMessage('土地を削除しました');
            }, function (SqlError $error) use ($p, $land) {
                Server::getInstance()->getLogger()->error("[LandSQL] $land->name の削除中に" . $error->getErrorMessage());
                $p->sendMessage('エラーが発生しました');
            });
    }

    public function addLog(string $xuid, string $type, ?string $subType, ?string $value, ?string $time, ?Closure $func, ?Closure $failure): void
    {
        if ($time === 'now') $time = date(SQLConst::DATE_FORMAT);
        $this->db->executeInsert('coral_reef.log.add', ['xuid' => intval($xuid), 'type' => $type, 'subtype' => $subType, 'value' => $value,
            'time' => $time], $func, $failure);
    }

    public function getLog(string $xuid, string $type, Closure $func, Closure $failure): void
    {
        $this->db->executeSelect('coral_reef.log.get.type', ['xuid' => intval($xuid), 'type' => $type], $func, $failure);
    }


    private function noticeByXUid(string $xuid, string $notice): Closure
    {
        return function () use ($notice, $xuid) {
            foreach (Server::getInstance()->getOnlinePlayers() as $p) {
                if ($p->getXuid() === $xuid) $p->sendMessage($notice);
            }
        };
    }

    private function createTable(): void
    {
        $this->db->executeGeneric('coral_reef.init.tables.user');
        $this->db->executeGeneric('coral_reef.init.tables.ban');
        $this->db->executeGeneric('coral_reef.init.tables.warp');
        $this->db->executeGeneric('coral_reef.init.tables.land');
        $this->db->executeGeneric('coral_reef.init.tables.virtual_value');
        $this->db->executeGeneric('coral_reef.init.tables.log');
    }
}
