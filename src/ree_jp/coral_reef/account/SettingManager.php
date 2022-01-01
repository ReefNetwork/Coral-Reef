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

namespace ree_jp\coral_reef\account;

use Closure;
use pocketmine\network\mcpe\protocol\GameRulesChangedPacket;
use pocketmine\network\mcpe\protocol\types\BoolGameRule;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use poggit\libasynql\SqlError;
use ree_jp\coral_reef\sql\SettingConst;
use ree_jp\coral_reef\sql\SQLConst;
use ree_jp\coral_reef\sql\SQLManager;

class SettingManager
{
    static array $settingCache = [];

    static function updateNickName(SQLManager $repo, Player $p): void
    {
        $repo->getValue($p->getXuid(), SQLConst::TYPE_SETTINGS, SettingConst::NICK_NAME,
            function (array $rows) use ($p) {
                $row = array_shift($rows);
                if (!isset($row['value'])) return;
                $nick = $row['value'];
                $p->sendMessage(TextFormat::GRAY . "現在のユーザーネームは" . $nick . "に設定されています");
                $p->setDisplayName($nick . TextFormat::DARK_GRAY . "(" . $p->getName() . ")" . TextFormat::RESET);
            }, function (SqlError $error) use ($p) {
                $p->sendMessage('ニックネームを読み込み中にエラーが発生しました');
                Server::getInstance()->getLogger()->warning("[setting nick]" . $error->getMessage());
            });
    }

    static function updateShowCoordinates(SQLManager $repo, Player $p): void
    {
        $repo->getValue($p->getXuid(), SQLConst::TYPE_SETTINGS, SettingConst::COORDINATES,
            function (array $rows) use ($p) {
                $row = array_shift($rows);
                $bool = false;
                if (isset($row['value'])) {
                    if ($row['value'] === 'true') $bool = true;
                }
                $pk = new GameRulesChangedPacket();
                $pk->gameRules["showCoordinates"] = new BoolGameRule(!$bool, true);
                $p->getNetworkSession()->sendDataPacket($pk);
            }, function (SqlError $error) use ($p) {
                $p->sendMessage('座標の設定を読み込み中にエラーが発生しました');
                Server::getInstance()->getLogger()->warning("[setting showCoordinates]" . $error->getMessage());
            });
    }

    static function updateOption(SQLManager $repo, Player $p, string $type): void
    {
        self::updateBoolOption($repo, $p->getXuid(), $type, function (SqlError $error) use ($p) {
            $p->sendMessage('設定を読み込み中にエラーが発生しました');
            Server::getInstance()->getLogger()->warning("[setting]" . $error->getMessage());
        });
    }

    static function isEnableOption(string $xuid, string $key): bool
    {
        if (isset(self::$settingCache[$xuid]) && isset(self::$settingCache[$xuid][$key])) {
            return self::$settingCache[$xuid][$key];
        }
        return false;
    }

    static function updateBoolOption(SQLManager $repo, string $xuid, string $type, Closure $failure): void
    {
        $repo->getValue($xuid, SQLConst::TYPE_SETTINGS, $type,
            function (array $rows) use ($type, $xuid) {
                $row = array_shift($rows);
                $bool = false;
                if (isset($row["value"]) && ($row["value"] === "true")) $bool = true;
                if (!isset(self::$settingCache[$xuid])) self::$settingCache[$xuid] = [];
                self::$settingCache[$xuid][$type] = $bool;
            }, $failure);
    }
}
