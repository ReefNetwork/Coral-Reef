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

namespace ree_jp\coral_reef\form\item;

use bbo51dog\bboform\element\Button;
use bbo51dog\bboform\element\ClosureButton;
use bbo51dog\bboform\form\ModalForm;
use pocketmine\block\Block;
use pocketmine\block\BlockIds;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\Player;
use pocketmine\scheduler\ClosureTask;
use ree_jp\coral_reef\account\AccountManager;
use ree_jp\coral_reef\CoralReefPlugin;

class HerbicideForm
{
    static function sendForm(Player $p): void
    {
        $item = $p->getInventory()->getItemInHand();
        $nbt = $item->getNamedTagEntry("herbicide_scale");
        if (!$nbt instanceof CompoundTag) {
            return;
        }

        $weight = $nbt->getInt("weight", 0);
        $height = $nbt->getInt("height", 0);

        $form = new ModalForm(new ClosureButton("使用する", null, function (Player $p) use ($item, $height, $weight): void {
            if (!$p->getInventory()->getItemInHand()->equalsExact($item)) {
                $p->sendMessage("エラーが発生しました");
                return;
            }
            $p->getInventory()->setItemInHand($item->setCount($item->getCount() - 1));
            $p->sendMessage(self::calculation($weight, $height) . "秒かかります");
            self::herbicide($p, $weight, $height, $weight, $weight, $height, 0);
        }), new Button("キャンセル"));
        $form->setTitle("Confirm")->setText("本当に除草剤を使用しますか?\n範囲内のすべての原木と葉を破壊します\n範囲はプレイヤーの位置が中心になります" .
            "\n\n範囲\n半径: $weight ブロック\n高さ: 上下$height ブロック");
        $p->sendForm($form);
    }

    private static function calculation(int $weight, int $height): float
    {
        $edge = $weight * 2 + 1;
        $blocks = $edge * $edge * $height;
        return $blocks / 10000;
    }

    private static function herbicide(Player $p, int $weight, int $height, int $x, int $z, int $relativeHeight, int $count): void
    {
        $xuid = $p->getXuid();
        $methodCount = 0;
        AccountManager::setValue($xuid, "skill_active");
        var_dump($x);
        var_dump(-$weight);
        for (; $x >= -$weight; --$x) {
            for (; $z >= -$weight; --$z) {
                for (; $relativeHeight >= -$height; --$relativeHeight) {
                    var_dump("a");
                    $check = $p->add($x, $relativeHeight, $z);
                    $bl = $p->getLevel()->getBlock($check);
                    if (in_array($bl->getId(), [BlockIds::LEAVES, BlockIds::LEAVES2, BlockIds::LOG, BlockIds::LOG2])) {
                        var_dump("b");
                        $event = new BlockBreakEvent($p, $bl, $p->getInventory()->getItemInHand(), true);
                        $event->call();
                        if (!$event->isCancelled()) {
                            $p->getLevel()->setBlock($check, Block::get(BlockIds::AIR));
                            $count++;
                        }
                    }
                    $methodCount++;
                    if ($methodCount > 500) {
                        var_dump("c");
                        CoralReefPlugin::$plugin->getScheduler()->scheduleDelayedTask(new ClosureTask(
                            function () use ($count, $relativeHeight, $z, $x, $height, $weight, $p): void {
                                self::herbicide($p, $weight, $height, $x, $z, --$relativeHeight, $count);
                            }), 1);
                        return;
                    }
                }
            }
        }
        AccountManager::setValue($xuid, "skill_active", 0);
        $p->sendMessage($count . "ブロックを破壊しました");
    }
}
