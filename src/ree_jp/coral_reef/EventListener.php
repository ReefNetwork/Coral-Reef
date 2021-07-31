<?php


namespace ree_jp\coral_reef;


use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerPreLoginEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\utils\TextFormat;
use ree_jp\coral_reef\account\AccountManager;

class EventListener implements Listener
{
    public function onPreLogin(PlayerPreLoginEvent $ev): void
    {
        $p = $ev->getPlayer();
        $reason = AccountManager::checkUser($p);
        if (is_string($reason)) $p->kick("Banされています" . TextFormat::EOL . $reason);
    }

    public function onJoin(PlayerJoinEvent $ev): void
    {
        $p = $ev->getPlayer();

        AccountManager::userJoin($p);
        if (is_null($p->getLastPlayed())) {
            $ev->setJoinMessage($p->getName() . 'さんが初めて参加しました');
        } else {
            $ev->setJoinMessage($p->getName() . 'さんが参加しました');
        }
    }

    public function onQuit(PlayerQuitEvent $ev): void
    {
        $p = $ev->getPlayer();

        AccountManager::userQuit($p);
        $ev->setQuitMessage(TextFormat::GRAY . $p->getName() . 'さんが' . $ev->getQuitReason() . '退出しました');
    }
}
