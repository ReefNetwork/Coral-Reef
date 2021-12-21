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

use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\Item;
use pocketmine\item\ItemIds;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\StringTag;

class ConvertItems extends ReefItems
{
    const MONEY_1000 = "money_1000";
    const MONEY_100000 = "money_100000";
    const NORMAL_TICKETS_FRAGMENT = "normal_tickets_fragment";
    const HERBICIDE = "herbicide";

    static function registerItems(): void
    {
        foreach ([self::MONEY_1000, self::MONEY_100000, self::NORMAL_TICKETS_FRAGMENT, self::HERBICIDE] as $key) {
            Item::addCreativeItem(self::getItem(0, $key));
        }
    }

    static function getItem(string $xuid, string $type, int $durable = 0): ?Item
    {
        switch ($type) {
            case self::MONEY_1000:
                $item = Item::get(ItemIds::GOLD_INGOT);
                $item->setNamedTagEntry(new StringTag(self::REEF_SP_ITEM, self::MONEY_1000));
                $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::INFINITY), 1));
                $item->setCustomName("1000円");
                break;
            case self::MONEY_100000:
                $item = Item::get(ItemIds::GOLD_INGOT);
                $item->setNamedTagEntry(new StringTag(self::REEF_SP_ITEM, self::MONEY_100000));
                $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::INFINITY), 1));
                $item->setCustomName("10万円");
                break;
            case self::NORMAL_TICKETS_FRAGMENT:
                $item = Item::get(ItemIds::PAPER);
                $item->setNamedTagEntry(new StringTag(self::REEF_SP_ITEM, self::NORMAL_TICKETS_FRAGMENT));
                $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::INFINITY), 1));
                $item->setCustomName("ノーマルガチャチケットのかけら");
                $item->setLore(["このかけらを10個集めるとノーマルガチャチケットを受け取れます"]);
                break;
            case self::HERBICIDE:
                $item = Item::get(ItemIds::DYE, 10);
                $item->setNamedTagEntry(new StringTag(self::REEF_SP_ITEM, self::HERBICIDE));
                $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::INFINITY), 1));
                $item->setCustomName("除草剤");
                $item->setLore(["原木と葉っぱを破壊できます"]);
                $nbt = new CompoundTag("herbicide_scale", [new IntTag("weight", 30), new IntTag("height", 10)]);
                $item->setNamedTagEntry($nbt);
                break;
            default:
                return null;
        }
        return $item;
    }
}
