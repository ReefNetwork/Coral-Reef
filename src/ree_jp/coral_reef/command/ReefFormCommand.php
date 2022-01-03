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
use ree_jp\coral_reef\account\AccountStore;
use ree_jp\coral_reef\form\command\LandForm;
use ree_jp\coral_reef\land\LandStore;
use ree_jp\coral_reef\sql\SQLManager;

class ReefFormCommand extends Command implements PluginOwned
{
    public function __construct(private Plugin $owner, private SQLManager $repo, private AccountStore $accountStore, private LandStore $landStore)
    {
        parent::__construct("reef-form", "reef form command");
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args)
    {
        if (!$sender instanceof Player || !isset($args[0])) {
            $sender->sendMessage("コマンドが間違っています");
            return;
        }

        switch ($args[0]) {
            case "land":
                LandForm::sendForm($this->repo, $this->accountStore, $this->landStore, $sender);
                break;
        }
    }

    public function getOwningPlugin(): Plugin
    {
        return $this->owner;
    }
}
