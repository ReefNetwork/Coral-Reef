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

namespace ree_jp\coral_reef\gatya\items;

use pocketmine\inventory\CreativeInventory;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\enchantment\VanillaEnchantments;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;

class NormalItems extends ReefItems
{
    static function registerItems(): void
    {
        foreach ([1, 2, 3, 4, 5, 6] as $key) {
            CreativeInventory::getInstance()->add(self::getItem(0, $key));
        }
    }

    static function getItemInt(string $xuid, int $type, int $durable = 0): ?Item
    {
        return self::getItem($xuid, strval($type), $durable);
    }

    static function getItem(string $xuid, string $type, int $durable = 0): ?Item
    {
        switch (intval($type)) {
            case 1:
                $item = VanillaItems::IRON_PICKAXE()->setDamage($durable);
                $item->setCustomName('かたいツルハシ');
                $item->addEnchantment(new EnchantmentInstance(VanillaEnchantments::UNBREAKING(), 3));
                break;
            case 2:
                $item = VanillaItems::IRON_SHOVEL()->setDamage($durable);
                $item->setCustomName('かたいシャベル');
                $item->addEnchantment(new EnchantmentInstance(VanillaEnchantments::UNBREAKING(), 3));
                break;
            case 3:
                $item = VanillaItems::IRON_AXE()->setDamage($durable);
                $item->setCustomName('かたい斧');
                $item->addEnchantment(new EnchantmentInstance(VanillaEnchantments::UNBREAKING(), 3));
                break;
            case 4:
                $item = VanillaItems::IRON_PICKAXE();
                $item->setCustomName('はやいツルハシ');
                $item->addEnchantment(new EnchantmentInstance(VanillaEnchantments::EFFICIENCY(), 2));
                break;
            case 5:
                $item = VanillaItems::IRON_SHOVEL()->setDamage($durable);
                $item->setCustomName('かたいシャベル');
                $item->addEnchantment(new EnchantmentInstance(VanillaEnchantments::EFFICIENCY(), 2));
                break;
            case 6:
                $item = VanillaItems::IRON_AXE()->setDamage($durable);
                $item->setCustomName('はやい斧');
                $item->addEnchantment(new EnchantmentInstance(VanillaEnchantments::EFFICIENCY(), 2));
                break;
            case 7:
                $item = VanillaItems::STEAK()->setCount(4);
                break;
            default:
                return null;
        }
        return $item;
    }
}
