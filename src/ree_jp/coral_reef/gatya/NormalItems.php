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

use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\Item;
use pocketmine\item\ItemIds;

class NormalItems extends ReefItems
{
    static function registerItems(): void
    {
        foreach ([1, 2, 3, 4, 5, 6] as $key) {
            Item::addCreativeItem(self::getItem(0, $key));
        }
    }

    static function getItem(string $xuid, int $type): ?Item
    {
        switch ($type) {
            case 1:
                $item = Item::get(ItemIds::IRON_PICKAXE);
                $item->setCustomName('かたいツルハシ');
                $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::UNBREAKING), 10));
                break;
            case 2:
                $item = Item::get(ItemIds::IRON_SHOVEL);
                $item->setCustomName('かたいシャベル');
                $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::UNBREAKING), 3));
                break;
            case 3:
                $item = Item::get(ItemIds::IRON_AXE);
                $item->setCustomName('かたい斧');
                $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::UNBREAKING), 3));
                break;
            case 4:
                $item = Item::get(ItemIds::IRON_PICKAXE);
                $item->setCustomName('はやいツルハシ');
                $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::EFFICIENCY), 2));
                break;
            case 5:
                $item = Item::get(ItemIds::IRON_SHOVEL);
                $item->setCustomName('かたいシャベル');
                $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::EFFICIENCY), 2));
                break;
            case 6:
                $item = Item::get(ItemIds::IRON_AXE);
                $item->setCustomName('はやい斧');
                $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::EFFICIENCY), 2));
                break;
            case 7:
                $item = Item::get(ItemIds::STEAK, 4);
                break;
            default:
                return null;
        }
        return $item;
    }
}
