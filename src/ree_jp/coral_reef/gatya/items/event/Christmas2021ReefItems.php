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

namespace ree_jp\coral_reef\gatya\items\event;

use pocketmine\block\BlockIds;
use pocketmine\item\Durable;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\Item;
use pocketmine\item\ItemIds;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\utils\TextFormat;
use ree_jp\coral_reef\account\AccountManager;
use ree_jp\coral_reef\gatya\items\ReefItems;

class Christmas2021ReefItems extends ReefItems
{
    const PICKAXE = "christmas_2021_reef_pickaxe";
    const SHOVEL = "christmas_2021_reef_shovel";
    const AXE = "christmas_2021_reef_axe";
    const HOE = "christmas_2021_reef_hoe";

    static function registerItems(): void
    {
        foreach ([self::PICKAXE, self::SHOVEL, self::AXE, self::HOE] as $key) {
            Item::addCreativeItem(self::getItem(0, $key));
        }
    }

    static function getItem(string $xuid, string $type, int $durable = 0): ?Item
    {
        switch ($type) {
            case self::PICKAXE:
                $item = Item::get(ItemIds::DIAMOND_PICKAXE);
                if (!$item instanceof Durable) return null;

                $item->setNamedTagEntry(new StringTag(self::REEF_SP_ITEM, self::PICKAXE));
                $item->setCustomName(TextFormat::DARK_GREEN . "Christmas" . TextFormat::RED . "2021" .
                    TextFormat::GREEN . 'Reef' . TextFormat::GOLD . 'Pickaxe');
                $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::EFFICIENCY), 10));
                $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::SILK_TOUCH), 10));
                break;
            case self::SHOVEL:
                $item = Item::get(ItemIds::DIAMOND_SHOVEL);
                if (!$item instanceof Durable) return null;

                $item->setNamedTagEntry(new StringTag(self::REEF_SP_ITEM, self::SHOVEL));
                $item->setCustomName(TextFormat::DARK_GREEN . "Christmas" . TextFormat::RED . "2021" .
                    TextFormat::GREEN . 'Reef' . TextFormat::GOLD . 'Shovel');
                $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::EFFICIENCY), 10));
                $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::SILK_TOUCH), 10));
                break;
            case self::AXE:
                $item = Item::get(ItemIds::DIAMOND_AXE);
                if (!$item instanceof Durable) return null;

                $item->setNamedTagEntry(new StringTag(self::REEF_SP_ITEM, self::AXE));
                $item->setCustomName(TextFormat::DARK_GREEN . "Christmas" . TextFormat::RED . "2021" .
                    TextFormat::GREEN . 'Reef' . TextFormat::GOLD . 'Axe');
                $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::EFFICIENCY), 10));
                $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::SILK_TOUCH), 10));
                break;
            case self::HOE:
                $item = Item::get(ItemIds::DIAMOND_HOE);
                if (!$item instanceof Durable) return null;

                $item->setNamedTagEntry(new StringTag(self::REEF_SP_ITEM, self::HOE));
                $item->setCustomName(TextFormat::DARK_GREEN . "Christmas" . TextFormat::RED . "2021" .
                    TextFormat::GREEN . 'Reef' . TextFormat::GOLD . 'Hoe');
                $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::EFFICIENCY), 10));
                $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::SILK_TOUCH), 10));
                break;
            default:
                return null;
        }
        $item->setUnbreakable();
        $item->setLore(["使用時:水を凍らすブロックが雪に変わります"]);
        $item->setNamedTagEntry(new IntTag("frozen_block", BlockIds::SNOW_BLOCK));
        $lore = $item->getLore();
        $lore[] = "所有者: " . AccountManager::getUserName($xuid);
        $item->setLore($lore);
        $item->setNamedTagEntry(new StringTag("owner", $xuid));
        return $item;
    }
}
