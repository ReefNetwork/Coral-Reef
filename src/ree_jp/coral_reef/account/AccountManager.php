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
use Frago9876543210\EasyForms\elements\Button;
use Frago9876543210\EasyForms\forms\MenuForm;
use pocketmine\Player;
use pocketmine\scheduler\ClosureTask;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use ree_jp\coral_reef\CoralReefPlugin;
use ree_jp\coral_reef\sql\SettingConst;
use ree_jp\coral_reef\sql\SQLManager;

class AccountManager
{
    static array $wait = [];

    static function setTimer(string $xuid, int $time = 10): void
    {
        self::$wait[$xuid] = $time;
        CoralReefPlugin::$plugin->getScheduler()->scheduleDelayedTask(
            new ClosureTask(function () use ($xuid) {
                if (array_key_exists($xuid, AccountManager::$wait)) {
                    unset(AccountManager::$wait[$xuid]);
                }
            }), $time);
    }

    /**
     * @param string $xuid
     * @return bool
     * タイマーが残ってればtrue
     */
    static function hasTimer(string $xuid): bool
    {
        return array_key_exists($xuid, self::$wait);
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
    }

    static function sendMenu(Player $p): void
    {
        $xuid = $p->getXuid();
        if (self::hasTimer($xuid)) return;
        self::setTimer($xuid);

        $level = 'loading';
        $necessaryExperience = 'loading';
        try {
            $user = SQLManager::$manager->getUser($xuid);
            $level = $user->level;
            $necessaryExperience = $user->necessaryExperience;
        } catch (Exception $e) {
            Server::getInstance()->getLogger()->error('ユーザーデータの取得中に' . $e->getMessage());
        }
        $p->sendForm(new MenuForm('ReefServer Menu', "レベル : $level \nレベルアップまで : $necessaryExperience", [new Button('ワールド移動'), new Button('設定')],
            function (Player $p, Button $button) {
                switch ($button->getValue()) {
                    case 0:
                        $p->sendForm(new MenuForm('Menu -> World', '移動するワールドを選択してください'));
                        break;

                    case 1:
                        $p->sendForm(new MenuForm('Menu -> Setting'));
                        break;

                    default:
                        $p->sendMessage('ページを開けませんでした');
                }
            }));
    }
}
