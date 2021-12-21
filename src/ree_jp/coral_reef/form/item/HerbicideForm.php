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
use ree_jp\coral_reef\account\AccountManager;

class HerbicideForm
{
    static function sendForm(Player $p): void
    {
        $nbt = $p->getInventory()->getItemInHand()->getNamedTagEntry("herbicide_scale");
        if (!$nbt instanceof CompoundTag) {
            return;
        }

        $weight = $nbt->getInt("weight", 0);
        $height = $nbt->getInt("height", 0);

        $form = new ModalForm(new ClosureButton("使用する", null, function (Player $p) use ($height, $weight): void {
            $count = self::herbicide($p, $weight, $height);
            $p->sendMessage("除草剤を使用して$count ブロックを破壊しました");
        }), new Button("キャンセル"));
        $form->setTitle("Confirm")->setText("本当に除草剤を使用しますか?\n範囲内のすべての原木と葉を破壊します\n範囲はプレイヤーの位置が中心になります" .
            "\n\n範囲\n半径: $weight ブロック\n高さ: 上下$height ブロック");
    }

    private static function herbicide(Player $p, int $weight, int $height): int
    {
        $xuid = $p->getXuid();
        $count = 0;
        AccountManager::setValue($xuid, "skill_active");
        AccountManager::setValue($xuid, "skill_active", 0);
        for ($x = $weight; $x >= -$weight; $x--) {
            for ($z = $weight; $z >= -$weight; $z--) {
                for ($relativeHeight = $height; $relativeHeight >= -$height; $relativeHeight--) {
                    $check = $p->add($x, $relativeHeight, $z);
                    $bl = $p->getLevel()->getBlock($check);
                    if (in_array($bl->getId(), [BlockIds::LEAVES, BlockIds::LEAVES2, BlockIds::LOG, BlockIds::LOG2])) {
                        $event = new BlockBreakEvent($p, $bl, $p->getInventory()->getItemInHand(), true);
                        $event->call();
                        if (!$event->isCancelled()) {
                            $p->getLevel()->setBlock($check, Block::get(BlockIds::AIR));
                            $count++;
                        }
                    }
                }
            }
        }
        return $count;
    }
}
