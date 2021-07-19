<?php


namespace ree_jp\coral_reef;


use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerPreLoginEvent;

class EventListener implements Listener
{
    public function onPreLogin(PlayerPreLoginEvent $ev): void
    {
        $p = $ev->getPlayer();
    }

    public function onJoin(PlayerJoinEvent $ev): void
    {

    }
}
