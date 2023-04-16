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
use pocketmine\utils\TextFormat;
use pocketmine\Server;

class AcceptCommand extends Command implements PluginOwned
{

    public function __construct(private Plugin $owner)
    {
        parent::__construct("accept", "プレイヤーからのテレポートを承諾します", null, ["acc"]);
        //$this->setPermission("coral_reef.command.menu"); 権限はymlも変えないとだからとりあえず消す
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args)
    {
        //if (!$this->testPermission($sender)) return;
        if (!$sender instanceof Player) {
            $sender->sendMessage("このコマンドはプレイヤー専用です");
            return;
        }

        if(!isset($args[0])) {
            $sender->sendMessage("Usage: /acc <name>");
            return;
        }
        
        $names = array();
        foreach(Server::getInstance()->getOnlinePlayers() as $player) {
            array_push($names, $player->getName());
        }

        usort($names, function ($a, $b) {
            return strlen($a) - strlen($b);
        });
        foreach ($names as $string) {
            if (strpos($string, $args[0]) !== false) {
                if ($accountStore->getValue($sender->getXuid(), ($p->getXuid(). "to" .$sender->getXuid()))) {
                    $p->sendMessage($sender->getName() . "さんへのテレポートが§a承諾§rされました");
                    $sender->sendMessage("テレポートリクエストを§a承諾§rしました");
                    $p->teleport($sender->getPosition());
                } else
                    $sender->sendMessage($p->getName() ."さんからは申請が来ていません");
            }
        }
        
        $sender->sendMessage("該当するプレイヤーはいませんでした");
    }

    public function getOwningPlugin(): Plugin
    {
        return $this->owner;
    }
  
}
