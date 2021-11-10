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

use pocketmine\command\CommandSender;
use pocketmine\command\PluginCommand;
use pocketmine\item\Durable;
use pocketmine\item\Item;
use pocketmine\item\ItemIds;
use pocketmine\nbt\tag\ByteTag;
use pocketmine\Player;
use pocketmine\plugin\Plugin;
use ree_jp\coral_reef\form\GatyaForm;
use ree_jp\coral_reef\quest\QuestListener;
use ree_jp\coral_reef\sql\SQLConst;
use ree_jp\coral_reef\sql\SQLManager;

class ReefCommand extends PluginCommand
{
    public function __construct(Plugin $owner)
    {
        parent::__construct('reef', $owner);
        $this->setUsage('Reef Command');
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
                    $pickaxe = Item::get(ItemIds::STONE_PICKAXE);
                    $pickaxe->setCustomName('初期装備(ツルハシ)');
                    if ($pickaxe instanceof Durable) $pickaxe->setUnbreakable();
                    $sender->getInventory()->addItem($pickaxe);
                    $shovel = Item::get(ItemIds::WOODEN_SHOVEL);
                    $shovel->setCustomName('初期装備(シャベル)');
                    if ($shovel instanceof Durable) $shovel->setUnbreakable();
                    $sender->getInventory()->addItem($shovel);
                    $sender->sendMessage('初期装備を配布しました');
                    QuestListener::callSubscribedQuest($sender->getXuid(), QuestListener::GET_INIT_TOOL, null);
                    return;
                case "food":
                    $food = Item::get(ItemIds::MELON)->setCustomName("無限すいか")->setLore(["食べても食べても減らない不思議なすいか"]);
                    $food->setNamedTagEntry(new ByteTag("reef_infinite_food"));
                    $sender->getInventory()->addItem($food);
                    $sender->sendMessage("無限スイカを配布しました");
                    return;
                case "gatya":
                    GatyaForm::sendGatyaForm($sender);
                    return;
                case "test":
                    $sender->sendMessage('super test message yeah');
                    break;
                case "admin":
                    if (!$sender->isOp() || !isset($args[1])) {
                        $sender->sendMessage("yhe_eee_eeeee_eeeee_eeeee_eeeee_eeeee");
                        return;
                    }
                    switch ($args[1]) {
                        case SQLConst::ENV_EXP_BUF:
                        case SQLConst::ENV_HASTE_EFFECT:
                            if (!isset($args[2]) || !is_numeric($args[2])) {
                                $sender->sendMessage("引数が間違ってる");
                                return;
                            }
                            SQLManager::$manager->setValue(0, SQLConst::TYPE_ENV, $args[1], $args[2], null);
                            $sender->sendMessage("反映には最大1分かかります");
                            break;
                        default:
                            $sender->sendMessage("そのadminコマンドはない!!!!!!!!");
                            break;
                    }
                    break;
            }
        }
        $sender->sendMessage('そのコマンドは間違っています');
    }
}
