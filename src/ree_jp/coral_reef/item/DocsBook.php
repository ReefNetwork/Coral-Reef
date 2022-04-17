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

namespace ree_jp\coral_reef\item;

use pocketmine\item\ItemIdentifier;
use pocketmine\item\ItemIds;
use pocketmine\item\ItemUseResult;
use pocketmine\player\Player;

class DocsBook extends ClickItem
{
    public function __construct()
    {
        parent::__construct(new ItemIdentifier(ItemIds::BOOK, 1), "機能説明");
    }

    static function onActive(Player $p, ClickItem $item): ItemUseResult
    {
        if (!self::markCoolTime($p, $item)) return ItemUseResult::FAIL();
        $p->getServer()->dispatchCommand($p, "exe-p wp-view category 104");
        return ItemUseResult::SUCCESS();
    }
}