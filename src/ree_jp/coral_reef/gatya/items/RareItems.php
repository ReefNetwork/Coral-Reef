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

class RareItems extends ReefItems
{
    const PICKAXE = "rare_pickaxe";
    const SHOVEL = "rare_shovel";

    static function getItem(string $xuid, string $type, int $durable = 0): ?Item
    {
        switch ($type) {
            case self::PICKAXE:
                $item = VanillaItems::DIAMOND_PICKAXE()->setDamage($durable);
                $nbt = $item->getNamedTag();
                $nbt->setString(self::REEF_SP_ITEM, self::PICKAXE);
                $item->setNamedTag($nbt);
                $item->setCustomName('レアツルハシ');
                break;
            case self::SHOVEL:
                $item = VanillaItems::DIAMOND_SHOVEL()->setDamage($durable);
                $nbt = $item->getNamedTag();
                $nbt->setString(self::REEF_SP_ITEM, self::SHOVEL);
                $item->setNamedTag($nbt);
                $item->setCustomName('レアシャベル');
                break;
            default:
                return null;
        }
        $item->addEnchantment(new EnchantmentInstance(VanillaEnchantments::UNBREAKING(), 1));
        return $item;
    }

    static function registerItems(): void
    {
        foreach ([self::PICKAXE, self::SHOVEL] as $key) {
            CreativeInventory::getInstance()->add(self::getItem(0, $key));
        }
    }
}
