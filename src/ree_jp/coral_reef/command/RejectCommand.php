<?php
/*
 *  CCCCC                        lll RRRRRR                 fff
 * CC    C  oooo  rr rr    aa aa lll RR   RR   eee    eee  ff
 * CC      oo  oo rrr  r  aa aaa lll RRRRRR  ee   e ee   e ffff
 * CC    C oo  oo rr     aa  aaa lll RR  RR  eeeee  eeeee  ff
 *  CCCCC   oooo  rr      aaa aa lll RR   RR  eeeee  eeeee ff
 *
 * Copyright (c) 2022. Ree-jp(https://ree-jp.net)
 */

namespace ree_jp\coral_reef\command;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\plugin\Plugin;
use pocketmine\plugin\PluginOwned;
use pocketmine\Server;
use ree_jp\coral_reef\account\AccountStore;
use ree_jp\coral_reef\StoreHouse;

class RejectCommand extends Command implements PluginOwned
{

    public function __construct(private Plugin $owner, private StoreHouse $store)
    {
        parent::__construct("reject", "プレイヤーからのテレポートを拒否します", null, ["rej"]);
        //$this->setPermission("coral_reef.command.menu"); 権限はymlも変えないとだからとりあえず消す
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): void
    {
        //if (!$this->testPermission($sender)) return;
        if (!$sender instanceof Player) {
            $sender->sendMessage("このコマンドはプレイヤー専用です");
            return;
        }

        if (!isset($args[0])) {
            $sender->sendMessage("Usage: /rej <name>");
            return;
        }

        /** @var AccountStore $accountStore */
        $accountStore = $this->store->get(AccountStore::class);

        foreach (Server::getInstance()->getOnlinePlayers() as $target) {
            if (str_contains(mb_strtolower($target->getName()), mb_strtolower($args[0]))) {
                $key = $target->getXuid() . "to" . $sender->getXuid();
                if ($accountStore->getValue($sender->getXuid(), $key)) {
                    $target->sendMessage($sender->getName() . "さんへのテレポートが§4拒否§rされました");
                    $sender->sendMessage("テレポートリクエストを§4拒否§rしました");
                    $accountStore->setValue($sender->getXuid(), $key, 0);
                } else $sender->sendMessage($target->getName() . "さんからは申請が来ていません");
                return;
            }
        }

        $sender->sendMessage("該当するプレイヤーはいませんでした");
    }

    public function getOwningPlugin(): Plugin
    {
        return $this->owner;
    }

}
