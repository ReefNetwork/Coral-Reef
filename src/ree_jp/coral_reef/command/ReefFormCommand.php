<?php
/*
 *  CCCCC                        lll RRRRRR                 fff
 * CC    C  oooo  rr rr    aa aa lll RR   RR   eee    eee  ff
 * CC      oo  oo rrr  r  aa aaa lll RRRRRR  ee   e ee   e ffff
 * CC    C oo  oo rr     aa  aaa lll RR  RR  eeeee  eeeee  ff
 *  CCCCC   oooo  rr      aaa aa lll RR   RR  eeeee  eeeee ff
 *
 * Copyright (c) 2021. Ree-jp(https://ree-jp.net)
 */

namespace ree_jp\coral_reef\command;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\plugin\Plugin;
use pocketmine\plugin\PluginOwned;
use ree_jp\coral_reef\form\land\LandForm;
use ree_jp\coral_reef\sql\RepositoryPool;
use ree_jp\coral_reef\StoreHouse;

class ReefFormCommand extends Command implements PluginOwned
{
    public function __construct(private Plugin $owner, private RepositoryPool $pool, private StoreHouse $store)
    {
        parent::__construct("reef-form", "reef form command");
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): void
    {
        if (!$sender instanceof Player || !isset($args[0])) {
            $sender->sendMessage("コマンドが間違っています");
            return;
        }

        if ($args[0] == "land") {
            LandForm::sendForm($this->pool, $this->store, $sender);
        }
    }

    public function getOwningPlugin(): Plugin
    {
        return $this->owner;
    }
}
