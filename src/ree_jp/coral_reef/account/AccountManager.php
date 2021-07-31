<?php


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

    static function userQuit(Player $p): void
    {

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
