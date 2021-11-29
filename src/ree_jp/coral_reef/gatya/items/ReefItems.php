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

use pocketmine\item\Durable;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\Item;
use pocketmine\item\ItemIds;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\utils\TextFormat;
use ree_jp\coral_reef\account\AccountManager;

class ReefItems
{
    const SPECIAL_EFFECT = "reef_special_effect";
    const REEF_SP_ITEM = "reef_special_item";
    const ITEM_RANK = "reef_item_rank";

    const PICKAXE = "reef_pickaxe";
    const SHOVEL = "reef_shovel";
    const AXE = "reef_axe";
    const HOE = "reef_hoe";
    const HELMET = "reef_helmet";
    const CHEST_PLATE = "reef_chest_plate";
    const LEGGINGS = "reef_leggings";
    const BOOTS = "reef_boot";

    static function getItem(string $xuid, string $type, int $durable = 0): ?Item
    {
        switch ($type) {
            case self::PICKAXE:
                $item = Item::get(ItemIds::DIAMOND_PICKAXE);
                if (!$item instanceof Durable) return null;

                $item->setNamedTagEntry(new StringTag(self::REEF_SP_ITEM, self::PICKAXE));
                $item->setCustomName(TextFormat::GREEN . 'Reef' . TextFormat::GOLD . 'Pickaxe');
                $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::EFFICIENCY), 10));
                $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::SILK_TOUCH), 10));
                break;
            case self::SHOVEL:
                $item = Item::get(ItemIds::DIAMOND_SHOVEL);
                if (!$item instanceof Durable) return null;

                $item->setNamedTagEntry(new StringTag(self::REEF_SP_ITEM, self::SHOVEL));
                $item->setCustomName(TextFormat::GREEN . 'Reef' . TextFormat::GOLD . 'Shovel');
                $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::EFFICIENCY), 10));
                $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::SILK_TOUCH), 10));
                break;
            case self::AXE:
                $item = Item::get(ItemIds::DIAMOND_AXE);
                if (!$item instanceof Durable) return null;

                $item->setNamedTagEntry(new StringTag(self::REEF_SP_ITEM, self::AXE));
                $item->setCustomName(TextFormat::GREEN . 'Reef' . TextFormat::GOLD . 'Axe');
                $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::EFFICIENCY), 10));
                $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::SILK_TOUCH), 10));
                break;
            case self::HOE:
                $item = Item::get(ItemIds::DIAMOND_HOE);
                if (!$item instanceof Durable) return null;

                $item->setNamedTagEntry(new StringTag(self::REEF_SP_ITEM, self::HOE));
                $item->setCustomName(TextFormat::GREEN . 'Reef' . TextFormat::GOLD . 'Hoe');
                $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::EFFICIENCY), 10));
                $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::SILK_TOUCH), 10));
                break;
            case self::HELMET:
                $item = Item::get(ItemIds::DIAMOND_HELMET);
                if (!$item instanceof Durable) return null;

                $item->setNamedTagEntry(new StringTag(self::REEF_SP_ITEM, self::HELMET));
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

                $item->setNamedTagEntry(new StringTag(self::REEF_SP_ITEM, self::CHEST_PLATE));
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

                $item->setNamedTagEntry(new StringTag(self::REEF_SP_ITEM, self::LEGGINGS));
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

                $item->setNamedTagEntry(new StringTag(self::REEF_SP_ITEM, self::BOOTS));
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
        $item->setLore(["所有者: " . AccountManager::getUserName($xuid)]);
        $item->setNamedTagEntry(new StringTag('owner', $xuid));
        return $item;
    }

    static function registerItems(): void
    {
        foreach ([self::PICKAXE, self::SHOVEL, self::AXE, self::HOE, self::HELMET, self::CHEST_PLATE, self::LEGGINGS, self::BOOTS] as $key) {
            Item::addCreativeItem(self::getItem(0, $key));
        }
    }

    static function registerAll(): void
    {
        self::registerItems();
        UltimateItems::registerItems();
        SuperItems::registerItems();
        RareItems::registerItems();
        NormalItems::registerItems();

        ConvertItems::registerItems();
    }
}
