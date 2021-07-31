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


use Exception;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use ree_jp\coral_reef\sql\SettingConst;
use ree_jp\coral_reef\sql\SQLManager;

class AccountManager
{
    const LEVEL_EXPERIMENT = [
        1 => 0, 2 => 100,
    ];

    static function checkUser(Player $p): ?string
    {
        $xuid = $p->getXuid();
        $name = $p->getName();
        $ip = $p->getAddress();

        try {
            return SQLManager::$manager->getBanReason($xuid, $ip);
        } catch (Exception $ex) {
            Server::getInstance()->getLogger()->error("[CheckBAN] $name の確認中に " . $ex->getMessage());
        }
        return null;
    }

    static function userJoin(Player $p): void
    {
        $xuid = $p->getXuid();
        $nick = "";

        try {
            SQLManager::$manager->addLog($xuid, 'join', 'now');
        } catch (Exception $e) {
            Server::getInstance()->getLogger()->error("[Log] {$p->getName()} の処理中に " . $e->getMessage());
        }
        try {
            $nick = SQLManager::$manager->getSetting($xuid, SettingConst::NICK_NAME);
        } catch (Exception $ex) {
            Server::getInstance()->getLogger()->error("[Nick] {$p->getName()} の確認中に " . $ex->getMessage());
        }
        if (!is_null($nick)) {
            $p->sendMessage(TextFormat::GRAY . "現在のユーザーネームは" . $nick . "に設定されています");
            $p->setNameTag($nick);
            $p->setDisplayName($nick);
        }
    }

    static function userQuit(Player $p, string $reason): void
    {
        $xuid = $p->getXuid();

        try {
            SQLManager::$manager->addLog($xuid, 'quit', 'now', null, $reason);
        } catch (Exception $e) {
            Server::getInstance()->getLogger()->error("[Log] {$p->getName()} の処理中に " . $e->getMessage());
        }
    }

    static function getLevel(int $experiment): int
    {
        foreach (self::LEVEL_EXPERIMENT as $constLevel => $constExperiment) {
            if ($constExperiment > $experiment) {
                return --$constLevel;
            }
        }
        return 99999;
    }
}
