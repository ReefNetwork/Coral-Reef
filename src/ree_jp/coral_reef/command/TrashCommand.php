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

use muqsit\invmenu\InvMenu;
use muqsit\invmenu\type\InvMenuTypeIds;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\inventory\Inventory;
use pocketmine\player\Player;
use pocketmine\plugin\Plugin;
use pocketmine\plugin\PluginOwned;
use pocketmine\utils\TextFormat;

class TrashCommand extends Command implements PluginOwned
{
    public function __construct(private Plugin $owner)
    {
        parent::__construct("trash", "ゴミ箱を開きます");
        $this->setPermission("coral_reef.command.trash");
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args)
    {
        if (!$this->testPermission($sender)) return;
        if (!$sender instanceof Player) {
            $sender->sendMessage("このコマンドはプレイヤー専用です");
            return;
        }

        $menu = InvMenu::create(InvMenuTypeIds::TYPE_DOUBLE_CHEST);
        $menu->setName("ゴミ箱" . TextFormat::DARK_GREEN . "※ゴミ箱から戻すことは出来ません");
        $menu->setInventoryCloseListener(function (Player $player, Inventory $inventory): void {
            $items = 0;
            foreach ($inventory->getContents() as $item) {
                $items += $item->getCount();
            }
            $inventory->clearAll();
            $player->sendMessage($items . "個のアイテムを捨てました");
        });
        $menu->send($sender);
    }

    public function getOwningPlugin(): Plugin
    {
        return $this->owner;
    }
}
