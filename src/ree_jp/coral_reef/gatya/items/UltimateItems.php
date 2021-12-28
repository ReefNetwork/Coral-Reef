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
use pocketmine\item\Durable;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\enchantment\VanillaEnchantments;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;

class UltimateItems extends ReefItems
{

    const PICKAXE = "ultimate_pickaxe";
    const SHOVEL = "ultimate_shovel";
    const AXE = "ultimate_axe";

    static function getItem(string $xuid, string $type, int $durable = 0): ?Item
    {
        switch ($type) {
            case self::PICKAXE:
                $item = VanillaItems::DIAMOND_PICKAXE()->setDamage($durable);
                $nbt = $item->getNamedTag();
                $nbt->setString(self::REEF_SP_ITEM, self::PICKAXE);
                $item->setNamedTag($nbt);
                $item->setCustomName('うるとらツルハシ');
                break;
            case self::SHOVEL:
                $item = VanillaItems::DIAMOND_SHOVEL()->setDamage($durable);
                $nbt = $item->getNamedTag();
                $nbt->setString(self::REEF_SP_ITEM, self::SHOVEL);
                $item->setNamedTag($nbt);
                $item->setCustomName('うるとらシャベル');
                break;
            case self::AXE:
                $item = VanillaItems::DIAMOND_AXE()->setDamage($durable);
                $nbt = $item->getNamedTag();
                $nbt->setString(self::REEF_SP_ITEM, self::AXE);
                $item->setNamedTag($nbt);
                $item->setCustomName('うるとらアックス');
                break;
            default:
                return null;
        }
        if (!$item instanceof Durable) return null;

        $item->addEnchantment(new EnchantmentInstance(VanillaEnchantments::UNBREAKING(), 5));
        $item->addEnchantment(new EnchantmentInstance(VanillaEnchantments::EFFICIENCY(), 3));
        $item->addEnchantment(new EnchantmentInstance(VanillaEnchantments::SILK_TOUCH(), 1));
        return $item;
    }

    static function registerItems(): void
    {
        foreach ([self::PICKAXE, self::SHOVEL, self::AXE] as $key) {
            CreativeInventory::getInstance()->add(self::getItem(0, $key));
        }
    }
}
