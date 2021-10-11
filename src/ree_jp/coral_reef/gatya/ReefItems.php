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
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\utils\TextFormat;

class ReefItems
{
    const SPECIAL_EFFECT = "reef_special_effect";
    const REEF_SP_ITEM = "reef_special_item";
    const ITEM_RANK = "reef_item_rank";

    const PICKAXE = 1;
    const SHOVEL = 2;
    const AXE = 3;
    const HOE = 4;
    const HELMET = 11;
    const CHEST_PLATE = 12;
    const LEGGINGS = 13;
    const BOOTS = 14;

    static function getItem(string $xuid, int $type): ?Item
    {
        switch ($type) {
            case self::PICKAXE:
                $item = Item::get(ItemIds::GOLDEN_PICKAXE);
                if (!$item instanceof Durable) return null;

                $item->setNamedTagEntry(new StringTag(self::REEF_SP_ITEM, 'reef_pickaxe'));
                $item->setCustomName(TextFormat::GREEN . 'Reef' . TextFormat::GOLD . 'Pickaxe');
                $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::EFFICIENCY), 10));
                $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::SILK_TOUCH), 10));
                break;
            case self::SHOVEL:
                $item = Item::get(ItemIds::GOLDEN_SHOVEL);
                if (!$item instanceof Durable) return null;

                $item->setNamedTagEntry(new StringTag(self::REEF_SP_ITEM, 'reef_shovel'));
                $item->setCustomName(TextFormat::GREEN . 'Reef' . TextFormat::GOLD . 'Shovel');
                $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::EFFICIENCY), 10));
                $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::SILK_TOUCH), 10));
                break;
            case self::AXE:
                $item = Item::get(ItemIds::GOLDEN_AXE);
                if (!$item instanceof Durable) return null;

                $item->setNamedTagEntry(new StringTag(self::REEF_SP_ITEM, 'reef_axe'));
                $item->setCustomName(TextFormat::GREEN . 'Reef' . TextFormat::GOLD . 'Axe');
                $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::EFFICIENCY), 10));
                $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::SILK_TOUCH), 10));
                break;
            case self::HOE:
                $item = Item::get(ItemIds::GOLDEN_HOE);
                if (!$item instanceof Durable) return null;

                $item->setNamedTagEntry(new StringTag(self::REEF_SP_ITEM, 'reef_hoe'));
                $item->setCustomName(TextFormat::GREEN . 'Reef' . TextFormat::GOLD . 'Hoe');
                $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::EFFICIENCY), 10));
                $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::SILK_TOUCH), 10));
                break;
            case self::HELMET:
                $item = Item::get(ItemIds::DIAMOND_HELMET);
                if (!$item instanceof Durable) return null;

                $item->setNamedTagEntry(new StringTag(self::REEF_SP_ITEM, 'reef_helmet'));
                $item->setCustomName(TextFormat::GREEN . 'Reef' . TextFormat::GOLD . 'Helmet');
                $item->setLore(["使用時:常時暗視状態になります(同じ効果は重複出来ません)", "", "ボーナス効果: ReefArmor",
                    "この効果を持つアイテムを4つ以上使用していた場合、スキル発動時のクールタイムが減ります"]);
                $item->setNamedTagEntry(new CompoundTag(self::SPECIAL_EFFECT, [
                    new IntTag("night_vision", 0),
                    new StringTag("context", "reef_armor"),
                ]));
                break;
            case self::CHEST_PLATE:
                $item = Item::get(ItemIds::DIAMOND_CHESTPLATE);
                if (!$item instanceof Durable) return null;

                $item->setNamedTagEntry(new StringTag(self::REEF_SP_ITEM, 'reef_chest_plate'));
                $item->setCustomName(TextFormat::GREEN . 'Reef' . TextFormat::GOLD . 'ChestPlate');
                $item->setLore(["使用時:常時満腹状態になります(同じ効果は重複出来ません)", "", "ボーナス効果: ReefArmor",
                    "この効果を持つアイテムを4つ以上使用していた場合、スキル発動時のクールタイムが減ります"]);
                $item->setNamedTagEntry(new CompoundTag(self::SPECIAL_EFFECT, [
                    new IntTag("saturation", 0),
                    new StringTag("context", "reef_armor"),
                ]));
                break;
            case self::LEGGINGS:
                $item = Item::get(ItemIds::DIAMOND_LEGGINGS);
                if (!$item instanceof Durable) return null;

                $item->setNamedTagEntry(new StringTag(self::REEF_SP_ITEM, 'reef_leggings'));
                $item->setCustomName(TextFormat::GREEN . 'Reef' . TextFormat::GOLD . 'Leggings');
                $item->setLore(["使用時:ジャンプ力が2上がります(同じ効果は重複出来ません)", "", "ボーナス効果: ReefArmor",
                    "この効果を持つアイテムを4つ以上使用していた場合、スキル発動時のクールタイムが減ります"]);
                $item->setNamedTagEntry(new CompoundTag(self::SPECIAL_EFFECT, [
                    new IntTag("jump_boost", 1),
                    new StringTag("context", "reef_armor"),
                ]));
                break;
            case self::BOOTS:
                $item = Item::get(ItemIds::DIAMOND_BOOTS);
                if (!$item instanceof Durable) return null;

                $item->setNamedTagEntry(new StringTag(self::REEF_SP_ITEM, 'reef_boot'));
                $item->setCustomName(TextFormat::GREEN . 'Reef' . TextFormat::GOLD . 'Boot');
                $item->setLore(["使用時:スピードが2上がります(同じ効果は重複出来ません)", "", "ボーナス効果: ReefArmor",
                    "この効果を持つアイテムを4つ以上使用していた場合、スキル発動時のクールタイムが減ります"]);
                $item->setNamedTagEntry(new CompoundTag(self::SPECIAL_EFFECT, [
                    new IntTag("speed", 1),
                    new StringTag("context", "reef_armor"),
                ]));
                break;
            default:
                return null;
        }
        $item->setUnbreakable();
        $item->setNamedTagEntry(new StringTag('owner', $xuid));
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
