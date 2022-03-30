<?php
/*
 *  CCCCC                        lll RRRRRR                 fff
 * CC    C  oooo  rr rr    aa aa lll RR   RR   eee    eee  ff
 * CC      oo  oo rrr  r  aa aaa lll RRRRRR  ee   e ee   e ffff
 * CC    C oo  oo rr     aa  aaa lll RR  RR  eeeee  eeeee  ff
 *  CCCCC   oooo  rr      aaa aa lll RR   RR  eeeee  eeeee ff
 *
 * Copyright (c) 2022-2021. Ree-jp(https://ree-jp.net)
 */

namespace ree_jp\coral_reef\command;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\item\Durable;
use pocketmine\item\VanillaItems;
use pocketmine\player\Player;
use pocketmine\plugin\Plugin;
use pocketmine\plugin\PluginOwned;
use ree_jp\coral_reef\quest\QuestListener;

class ReefCommand extends Command implements PluginOwned
{
    public function __construct(private Plugin $owner)
    {
        parent::__construct("reef");
        $this->setUsage("Reef Command");
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args)
    {
        if (!$sender instanceof Player) {
            $sender->sendMessage('このコマンドはプレイヤー専用です');
            return;
        }
        if (isset($args[0])) {
            switch ($args[0]) {
                case "init_tool":
                    $pickaxe = VanillaItems::IRON_PICKAXE();
                    $pickaxe->setCustomName("初期装備(ツルハシ)");
                    if ($pickaxe instanceof Durable) $pickaxe->setUnbreakable();
                    $sender->getInventory()->addItem($pickaxe);
                    $shovel = VanillaItems::IRON_SHOVEL();
                    $shovel->setCustomName("初期装備(シャベル)");
                    if ($shovel instanceof Durable) $shovel->setUnbreakable();
                    $sender->getInventory()->addItem($shovel);
                    $sender->sendMessage("初期装備を配布しました");
                    QuestListener::callSubscribedQuest($sender->getXuid(), QuestListener::GET_INIT_TOOL, null);
                    return;
                case "food":
                    $food = VanillaItems::BAKED_POTATO()->setCustomName("無限ぽていとぉ")->setLore(["A MAGICAL POTATO"]);
                    $nbt = $food->getNamedTag();
                    $nbt->setByte("reef_infinite_food", 1);
                    $food->setNamedTag($nbt);
                    $sender->getInventory()->addItem($food);
                    $sender->sendMessage("無限ポテトを配布しました");
                    return;
                case "test":
                    $sender->sendMessage('super test message yeah');
                    break;
            }
        }
        $sender->sendMessage('そのコマンドは間違っています');
    }

    public function getOwningPlugin(): Plugin
    {
        return $this->owner;
    }
}
