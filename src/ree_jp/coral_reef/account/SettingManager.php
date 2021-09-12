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

use pocketmine\network\mcpe\protocol\GameRulesChangedPacket;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use poggit\libasynql\SqlError;
use ree_jp\coral_reef\sql\SettingConst;
use ree_jp\coral_reef\sql\SQLConst;
use ree_jp\coral_reef\sql\SQLManager;

class SettingManager
{
    static array $settingCache = [];

    static function updateNickName(Player $p): void
    {
        SQLManager::$manager->getValue($p->getXuid(), SQLConst::TYPE_SETTINGS, SettingConst::NICK_NAME,
            function (array $rows) use ($p) {
                $row = array_shift($rows);
                if (!isset($row['value'])) return;
                $nick = $row['value'];
                $p->sendMessage(TextFormat::GRAY . "現在のユーザーネームは" . $nick . "に設定されています");
                $p->setNameTag($nick);
                $p->setDisplayName($nick);
            }, function (SqlError $error) use ($p) {
                $p->sendMessage('ニックネームを読み込み中にエラーが発生しました');
                Server::getInstance()->getLogger()->warning("[setting nick]" . $error->getMessage());
            });
    }

    static function updateShowCoordinates(Player $p, bool $bool = null): void
    {
        SQLManager::$manager->getValue($p->getXuid(), SQLConst::TYPE_SETTINGS, SettingConst::COORDINATES,
            function (array $rows) use ($p) {
                $row = array_shift($rows);
                $bool = false;
                if (isset($row['value'])) {
                    if ($row['value'] === 'true') $bool = true;
                }
                $pk = new GameRulesChangedPacket();
                $pk->gameRules["showCoordinates"] = [1, !$bool, true];
                $p->dataPacket($pk);
            }, function (SqlError $error) use ($p) {
                $p->sendMessage('座標の設定を読み込み中にエラーが発生しました');
                Server::getInstance()->getLogger()->warning("[setting showCoordinates]" . $error->getMessage());
            });
    }

    static function updateSneakSkill(Player $p, bool $bool = null): void
    {
        SQLManager::$manager->getValue($p->getXuid(), SQLConst::TYPE_SETTINGS, SettingConst::SNEAK_SKILL,
            function (array $rows) use ($p) {
                $row = array_shift($rows);
                $bool = false;
                if (isset($row['value'])) if ($row['value'] === 'true') $bool = true;
                if (!isset(self::$settingCache[$p->getXuid()])) self::$settingCache[$p->getXuid()] = [];
                self::$settingCache[$p->getXuid()][SettingConst::SNEAK_SKILL] = $bool;
            }, function (SqlError $error) use ($p) {
                $p->sendMessage('スキルの設定を読み込み中にエラーが発生しました');
                Server::getInstance()->getLogger()->warning("[setting showCoordinates]" . $error->getMessage());
            });
    }

    static function updateServerTip(Player $p, bool $bool = null): void
    {
        SQLManager::$manager->getValue($p->getXuid(), SQLConst::TYPE_SETTINGS, SettingConst::HIDE_SERVER_TIP,
            function (array $rows) use ($p) {
                $row = array_shift($rows);
                $bool = false;
                if (isset($row['value'])) if ($row['value'] === 'true') $bool = true;
                if (!isset(self::$settingCache[$p->getXuid()])) self::$settingCache[$p->getXuid()] = [];
                self::$settingCache[$p->getXuid()][SettingConst::HIDE_SERVER_TIP] = $bool;
            }, function (SqlError $error) use ($p) {
                $p->sendMessage('ヒントの設定を読み込み中にエラーが発生しました');
                Server::getInstance()->getLogger()->warning("[setting serverTip]" . $error->getMessage());
            });
    }

    static function isEnableOption(string $xuid, string $key): bool
    {
        if (isset(self::$settingCache[$xuid]) && isset(self::$settingCache[$xuid][$key])) {
            return self::$settingCache[$xuid][$key];
        }
        return false;
    }
}
