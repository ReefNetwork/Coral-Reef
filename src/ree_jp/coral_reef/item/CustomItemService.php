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

use pocketmine\item\Item;
use pocketmine\item\ItemFactory;

class CustomItemService
{
    static function registerAll(): void
    {
        ItemFactory::getInstance()->register(new DocsBook(), true);
    }

    static function get(int $id, int $meta): Item
    {
        $item = ItemFactory::getInstance()->get($id, $meta);
        return $item->setCustomName($item->getName());
    }
}