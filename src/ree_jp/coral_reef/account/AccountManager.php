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
use pocketmine\scheduler\ClosureTask;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use ree_jp\coral_reef\CoralReefPlugin;
use ree_jp\coral_reef\sql\SettingConst;
use ree_jp\coral_reef\sql\SQLManager;

class AccountManager
{

    static array $values = array();

    static function setValue(string $xuid, string $value, int $tick = null): void
    {
        $key = $xuid . ':' . $value;
        if ($tick === 0) {
            if (self::hasValue($xuid, $value)) {
                unset(self::$values[$key]);
            }
        } else {
            self::$values[$key] = $tick;
            if (is_int($tick)) {
                CoralReefPlugin::$plugin->getScheduler()->scheduleDelayedTask(
                    new ClosureTask(function () use ($key) {
                        if (array_key_exists($key, self::$values)) {
                            unset(self::$values[$key]);
                        }
                    }), $tick);
            }

        }
    }

    static function hasValue(string $xuid, string $value): bool
    {
        $key = $xuid . ':' . $value;
        return array_key_exists($key, self::$values);
    }

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
            $account = SQLManager::$manager->getUser($xuid);
            $account->save();
        } catch (Exception $e) {
            Server::getInstance()->getLogger()->error($p->getName() . 'のセーブができませんでした' . $e->getMessage());
        }
        try {
            SQLManager::$manager->addLog($xuid, 'quit', 'now', null, $reason);
        } catch (Exception $e) {
            Server::getInstance()->getLogger()->error("[Log] {$p->getName()} の処理中に " . $e->getMessage());
        }
        self::setValue($xuid, 'rejoin', 60 * 3 * 20);
    }
}
