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
use pocketmine\utils\TextFormat;

class ReefTools
{
    const PICKAXE = 1;
    const AXE = 2;
    const HOE = 3;

    static public function getReef(string $xuid, int $type): ?Item
    {
        switch ($type) {
            case self::PICKAXE:
                $item = Item::get(ItemIds::GOLDEN_PICKAXE);
                $item->setCustomName(TextFormat::GREEN . 'Reef' . TextFormat::GOLD . 'PICKAXE');
                break;
            case self::AXE:
                $item = Item::get(ItemIds::GOLDEN_AXE);
                $item->setCustomName(TextFormat::GREEN . 'Reef' . TextFormat::GOLD . 'AXE');
                break;
            case self::HOE:
                $item = Item::get(ItemIds::GOLDEN_HOE);
                $item->setCustomName(TextFormat::GREEN . 'Reef' . TextFormat::GOLD . 'HOE');
                break;
            default:
                return null;
        }
        if (!$item instanceof Durable) return null;

        $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::EFFICIENCY), 10));
        $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::SILK_TOUCH), 10));
        $item->setNamedTagEntry(new StringTag('owner', $xuid));
        $item->setUnbreakable();
        return $item;
    }
}
