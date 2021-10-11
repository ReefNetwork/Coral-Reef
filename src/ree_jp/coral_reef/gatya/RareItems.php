<?php
/*
 *  CCCCC                        lll RRRRRR                 fff
 * CC    C  oooo  rr rr    aa aa lll RR   RR   eee    eee  ff
 * CC      oo  oo rrr  r  aa aaa lll RRRRRR  ee   e ee   e ffff
 * CC    C oo  oo rr     aa  aaa lll RR  RR  eeeee  eeeee  ff
 *  CCCCC   oooo  rr      aaa aa lll RR   RR  eeeee  eeeee ff
 *
 * Copyright (c) 2021-2021. Ree-jp(https://ree-jp.net)
 */

namespace ree_jp\coral_reef\gatya;

use pocketmine\item\Durable;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\Item;
use pocketmine\item\ItemIds;
use pocketmine\nbt\tag\StringTag;

class RareItems extends ReefItems
{
    static function getItem(string $xuid, int $type): ?Item
    {
        switch ($type) {
            case self::PICKAXE:
                $item = Item::get(ItemIds::DIAMOND_PICKAXE);
                $item->setNamedTagEntry(new StringTag(self::REEF_SP_ITEM, 'rare_pickaxe'));
                $item->setCustomName('レアツルハシ');
                break;
            case self::SHOVEL:
                $item = Item::get(ItemIds::DIAMOND_SHOVEL);
                $item->setNamedTagEntry(new StringTag(self::REEF_SP_ITEM, 'rare_axe'));
                $item->setCustomName('レアシャベル');
                break;
            default:
                return null;
        }
        if (!$item instanceof Durable) return null;

        $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::UNBREAKING), 1));
        return $item;
    }

    static function registerItems(): void
    {
        foreach ([1, 2] as $key) {
            Item::addCreativeItem(self::getItem(0, $key));
        }
    }
}
