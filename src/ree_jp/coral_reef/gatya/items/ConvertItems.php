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

namespace ree_jp\coral_reef\gatya\items;

use pocketmine\item\Item;
use pocketmine\item\ItemIds;
use pocketmine\nbt\tag\StringTag;

class ConvertItems extends ReefItems
{
    const NORMAL_TICKETS_FRAGMENT = "normal_tickets_fragment";

    static function registerItems(): void
    {
        foreach ([self::NORMAL_TICKETS_FRAGMENT] as $key) {
            Item::addCreativeItem(self::getItem(0, $key));
        }
    }

    static function getItem(string $xuid, string $type, int $durable = 0): ?Item
    {
        switch ($type) {
            case self::NORMAL_TICKETS_FRAGMENT:
                $item = Item::get(ItemIds::NETHER_STAR);
                $item->setNamedTagEntry(new StringTag(self::REEF_SP_ITEM, self::NORMAL_TICKETS_FRAGMENT));
                $item->setCustomName("ノーマルガチャチケットのかけら");
                $item->setLore(["このかけらを10個集めるとノーマルガチャチケットを受け取れます"]);
                break;
            default:
                return null;
        }
        return $item;
    }
}
