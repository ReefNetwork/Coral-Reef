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

namespace ree_jp\coral_reef\skill;

use pocketmine\block\Block;
use pocketmine\block\BlockLegacyIds;
use pocketmine\item\Item;
use pocketmine\math\Facing;
use pocketmine\math\Vector3;
use pocketmine\player\Player;

class TreeBreakService
{
    const TREE_CUT = "tree_cut";

    static function runBreak(Player $p, Item $item, Vector3 $vec): void
    {
        // 起点のブロックは破壊しない
        $checkDone = [(string)$vec];
        $count = 0;
        foreach (Facing::ALL as $facing) {
            self::checkAllFacing($p, $item, $vec->getSide($facing), $checkDone, $count);
        }
    }

    private static function checkAllFacing(Player $p, Item $item, Vector3 $vec, array &$checkDone, int &$count): void
    {
        $checkDone[] = (string)$vec;
        $bl = $p->getWorld()->getBlock($vec);
        if (self::isTree($bl)) {
            $count++;
            BreakService::silentBreak($p->getWorld(), $bl, $item, $p);

            if ($count > 50) {
                // 50以上は一括破壊できないように
                return;
            }

            foreach (Facing::ALL as $facing) {
                $side = $vec->getSide($facing);
                if (!in_array((string)$side, $checkDone)) {
                    self::checkAllFacing($p, $item, $side, $checkDone, $count);
                }
            }
        }
    }

    private static function isTree(Block $bl): bool
    {
        return in_array($bl->getId(), [BlockLegacyIds::LEAVES, BlockLegacyIds::LEAVES2, BlockLegacyIds::LOG, BlockLegacyIds::LOG2]);
    }
}