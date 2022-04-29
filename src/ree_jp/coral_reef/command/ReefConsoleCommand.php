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
use pocketmine\item\Item;
use pocketmine\player\Player;
use pocketmine\plugin\Plugin;
use pocketmine\plugin\PluginOwned;
use ree_jp\coral_reef\account\AccountStore;
use ree_jp\coral_reef\gatya\items\SpecialItemService;
use ree_jp\coral_reef\sql\SQLConst;
use ree_jp\coral_reef\sql\SQLRepository;

class ReefConsoleCommand extends Command implements PluginOwned
{
    public function __construct(private Plugin $owner, private SQLRepository $repo, private AccountStore $store)
    {
        parent::__construct("reef-console");
        $this->setUsage("Reef Manage Command");
        $this->setAliases(["reef-c"]);
        $this->setPermission("coral_reef.command.reef_console");
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args)
    {
        if (!$this->testPermission($sender)) return;
        if (isset($args[0])) {

            switch ($args[0]) {
                case SQLConst::ENV_HASTE_EFFECT:
                case SQLConst::ENV_EXP_BUF:
                    if (!isset($args[1]) || !is_numeric($args[1])) {
                        $sender->sendMessage("引数が間違ってる");
                        return;
                    }
                    $this->repo->setValue(0, SQLConst::TYPE_ENV, $args[0], $args[1], null);
                    $sender->sendMessage("反映には最大1分かかります");
                    break;
                case "tool":
                    if (count($args) < 4) {
                        $sender->sendMessage("引数が間違ってる");
                        return;
                    }
                    $item = SpecialItemService::getRenewItem($args[1], $args[2], $args[3], $this->store);
                    if ($sender instanceof Player && $item instanceof Item) {
                        $sender->getInventory()->addItem($item);
                    }
                    break;
                default:
                    $sender->sendMessage("そのコマンドはない!!!!!!!!");
                    break;
            }
        }
    }

    public function getOwningPlugin(): Plugin
    {
        return $this->owner;
    }
}
