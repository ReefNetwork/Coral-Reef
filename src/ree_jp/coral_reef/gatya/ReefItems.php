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

class ReefItems
{
    const REEF_SP_ITEM = 'reef_special_item';
    const PICKAXE = 1;
    const SHOVEL = 2;
    const AXE = 3;
    const HOE = 4;

    static function getItem(string $xuid, int $type): ?Item
    {
        switch ($type) {
            case self::PICKAXE:
                $item = Item::get(ItemIds::GOLDEN_PICKAXE);
                $item->setNamedTagEntry(new StringTag(self::REEF_SP_ITEM, 'reef_pickaxe'));
                $item->setCustomName(TextFormat::GREEN . 'Reef' . TextFormat::GOLD . 'Pickaxe');
                break;
            case self::SHOVEL:
                $item = Item::get(ItemIds::GOLDEN_SHOVEL);
                $item->setNamedTagEntry(new StringTag(self::REEF_SP_ITEM, 'reef_shovel'));
                $item->setCustomName(TextFormat::GREEN . 'Reef' . TextFormat::GOLD . 'Shovel');
                break;
            case self::AXE:
                $item = Item::get(ItemIds::GOLDEN_AXE);
                $item->setNamedTagEntry(new StringTag(self::REEF_SP_ITEM, 'reef_axe'));
                $item->setCustomName(TextFormat::GREEN . 'Reef' . TextFormat::GOLD . 'Axe');
                break;
            case self::HOE:
                $item = Item::get(ItemIds::GOLDEN_HOE);
                $item->setNamedTagEntry(new StringTag(self::REEF_SP_ITEM, 'reef_hoe'));
                $item->setCustomName(TextFormat::GREEN . 'Reef' . TextFormat::GOLD . 'Hoe');
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

    static function registerItems(): void
    {
        $key = 1;
        while ($item = self::getItem(0, $key)) {
            Item::addCreativeItem($item);
            $key++;
        }
    }

    static function registerAll(): void
    {
        self::registerItems();
        UltimateItems::registerItems();
        SuperItems::registerItems();
        RareItems::registerItems();
        NormalItems::registerItems();
    }
}
