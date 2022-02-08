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

use pocketmine\inventory\CreativeInventory;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\enchantment\VanillaEnchantments;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\IntTag;

class ConvertItems extends ReefItems
{
    const MONEY_1000 = "money_1000";
    const MONEY_10000 = "money_10000";
    const MONEY_100000 = "money_100000";
    const NORMAL_TICKETS_FRAGMENT = "normal_tickets_fragment";
    const HERBICIDE = "herbicide";

    static function registerItems(): void
    {
        foreach ([self::MONEY_1000, self::MONEY_10000, self::MONEY_100000, self::NORMAL_TICKETS_FRAGMENT, self::HERBICIDE] as $key) {
            CreativeInventory::getInstance()->add(self::getItem(0, $key));
        }
    }

    static function getItem(string $xuid, string $type, int $durable = 0): ?Item
    {
        switch ($type) {
            case self::MONEY_1000:
                $item = VanillaItems::GOLD_INGOT();

                $nbt = $item->getNamedTag();
                $nbt->setString(self::REEF_SP_ITEM, self::MONEY_1000);
                $item->setNamedTag($nbt);
                $item->addEnchantment(new EnchantmentInstance(VanillaEnchantments::FLAME(), 1));

                $item->setCustomName("1000円");
                break;
            case self::MONEY_10000:
                $item = VanillaItems::GOLD_INGOT();

                $nbt = $item->getNamedTag();
                $nbt->setString(self::REEF_SP_ITEM, self::MONEY_10000);
                $item->setNamedTag($nbt);
                $item->addEnchantment(new EnchantmentInstance(VanillaEnchantments::FLAME(), 1));

                $item->setCustomName("1万円");
                break;
            case self::MONEY_100000:
                $item = VanillaItems::GOLD_INGOT();

                $nbt = $item->getNamedTag();
                $nbt->setString(self::REEF_SP_ITEM, self::MONEY_100000);
                $item->setNamedTag($nbt);
                $item->addEnchantment(new EnchantmentInstance(VanillaEnchantments::FLAME(), 1));

                $item->setCustomName("10万円");
                break;
            case self::NORMAL_TICKETS_FRAGMENT:
                $item = VanillaItems::PAPER();

                $nbt = $item->getNamedTag();
                $nbt->setString(self::REEF_SP_ITEM, self::NORMAL_TICKETS_FRAGMENT);
                $item->setNamedTag($nbt);
                $item->addEnchantment(new EnchantmentInstance(VanillaEnchantments::INFINITY(), 1));

                $item->setCustomName("ノーマルガチャチケットのかけら");
                $item->setLore(["このかけらを10個集めるとノーマルガチャチケットを受け取れます"]);
                break;
            case self::HERBICIDE:
                $item = VanillaItems::LIME_DYE();

                $nbt = $item->getNamedTag();
                $nbt->setString(self::REEF_SP_ITEM, self::HERBICIDE);
                $herbTag = new CompoundTag();
                $herbTag->setTag("weight", new IntTag(30));
                $herbTag->setTag("height", new IntTag(10));
                $nbt->setTag("herbicide_scale", $herbTag);
                $item->setNamedTag($nbt);
                $item->addEnchantment(new EnchantmentInstance(VanillaEnchantments::INFINITY(), 1));

                $item->setCustomName("除草剤");
                $item->setLore(["原木と葉っぱを破壊できます"]);
                break;
            default:
                return null;
        }
        return $item;
    }
}
