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
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\utils\TextFormat;
use ree_jp\coral_reef\gatya\items\event\Christmas2021ReefItems;
use ree_jp\coral_reef\skill\TreeBreakService;

class ReefItems
{
    const SPECIAL_EFFECT = "reef_special_effect";
    const REEF_SP_ITEM = "reef_special_item";

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
                $item = VanillaItems::DIAMOND_PICKAXE();
                $nbt = $item->getNamedTag();
                $nbt->setString(self::REEF_SP_ITEM, self::PICKAXE);
                $item->setNamedTag($nbt);
                $item->setCustomName(TextFormat::GREEN . 'Reef' . TextFormat::GOLD . 'Pickaxe');
                $item->addEnchantment(new EnchantmentInstance(VanillaEnchantments::EFFICIENCY(), 10));
                $item->addEnchantment(new EnchantmentInstance(VanillaEnchantments::SILK_TOUCH(), 10));
                break;
            case self::SHOVEL:
                $item = VanillaItems::DIAMOND_SHOVEL();
                $nbt = $item->getNamedTag();
                $nbt->setString(self::REEF_SP_ITEM, self::SHOVEL);
                $item->setNamedTag($nbt);
                $item->setCustomName(TextFormat::GREEN . 'Reef' . TextFormat::GOLD . 'Shovel');
                $item->addEnchantment(new EnchantmentInstance(VanillaEnchantments::EFFICIENCY(), 10));
                $item->addEnchantment(new EnchantmentInstance(VanillaEnchantments::SILK_TOUCH(), 10));
                break;
            case self::AXE:
                $item = VanillaItems::DIAMOND_AXE();
                $nbt = $item->getNamedTag();
                $nbt->setString(self::REEF_SP_ITEM, self::AXE);
                $nbt->setByte(TreeBreakService::TREE_CUT, 1);
                $item->setNamedTag($nbt);
                $item->setCustomName(TextFormat::GREEN . 'Reef' . TextFormat::GOLD . 'Axe');
                $item->addEnchantment(new EnchantmentInstance(VanillaEnchantments::EFFICIENCY(), 10));
                $item->addEnchantment(new EnchantmentInstance(VanillaEnchantments::SILK_TOUCH(), 10));
                $item->setLore(["スキル発動時:木を一括破壊します"]);
                break;
            case self::HOE:
                $item = VanillaItems::DIAMOND_HOE();
                $nbt = $item->getNamedTag();
                $nbt->setString(self::REEF_SP_ITEM, self::HOE);
                $item->setNamedTag($nbt);
                $item->setCustomName(TextFormat::GREEN . 'Reef' . TextFormat::GOLD . 'Hoe');
                $item->addEnchantment(new EnchantmentInstance(VanillaEnchantments::EFFICIENCY(), 10));
                $item->addEnchantment(new EnchantmentInstance(VanillaEnchantments::SILK_TOUCH(), 10));
                break;
            case self::HELMET:
                $item = VanillaItems::DIAMOND_HELMET();
                $nbt = $item->getNamedTag();
                $nbt->setString(self::REEF_SP_ITEM, self::HELMET);
                $effectTag = new CompoundTag();
                $effectTag->setInt("night_vision", 0);
                $effectTag->setString("context", "reef_armor");
                $nbt->setTag(self::SPECIAL_EFFECT, $effectTag);
                $item->setNamedTag($nbt);
                $item->setCustomName(TextFormat::GREEN . 'Reef' . TextFormat::GOLD . 'Helmet');
                $item->setLore(["使用時:常時暗視状態になります(同じ効果は重複出来ません)", "", "ボーナス効果: ReefArmor",
                    "この効果を持つアイテムを4つ以上使用していた場合、スキル発動時のクールタイムが3秒減少します"]);
                break;
            case self::CHEST_PLATE:
                $item = VanillaItems::DIAMOND_CHESTPLATE();
                $nbt = $item->getNamedTag();
                $nbt->setString(self::REEF_SP_ITEM, self::CHEST_PLATE);
                $effectTag = new CompoundTag();
                $effectTag->setInt("saturation", 0);
                $effectTag->setString("context", "reef_armor");
                $nbt->setTag(self::SPECIAL_EFFECT, $effectTag);
                $item->setNamedTag($nbt);
                $item->setCustomName(TextFormat::GREEN . 'Reef' . TextFormat::GOLD . 'ChestPlate');
                $item->setLore(["使用時:常時満腹状態になります(同じ効果は重複出来ません)", "", "ボーナス効果: ReefArmor",
                    "この効果を持つアイテムを4つ以上使用していた場合、スキル発動時のクールタイムが3秒減少します"]);
                break;
            case self::LEGGINGS:
                $item = VanillaItems::DIAMOND_LEGGINGS();
                $nbt = $item->getNamedTag();
                $nbt->setString(self::REEF_SP_ITEM, self::LEGGINGS);
                $effectTag = new CompoundTag();
                $effectTag->setInt("jump_boost", 1);
                $effectTag->setString("context", "reef_armor");
                $nbt->setTag(self::SPECIAL_EFFECT, $effectTag);
                $item->setNamedTag($nbt);
                $item->setCustomName(TextFormat::GREEN . 'Reef' . TextFormat::GOLD . 'Leggings');
                $item->setLore(["使用時:ジャンプ力が2上がります(同じ効果は重複出来ません)", "", "ボーナス効果: ReefArmor",
                    "この効果を持つアイテムを4つ以上使用していた場合、スキル発動時のクールタイムが3秒減少します"]);
                break;
            case self::BOOTS:
                $item = VanillaItems::DIAMOND_BOOTS();
                $nbt = $item->getNamedTag();
                $nbt->setString(self::REEF_SP_ITEM, self::BOOTS);
                $effectTag = new CompoundTag();
                $effectTag->setInt("speed", 1);
                $effectTag->setString("context", "reef_armor");
                $nbt->setTag(self::SPECIAL_EFFECT, $effectTag);
                $item->setNamedTag($nbt);
                $item->setCustomName(TextFormat::GREEN . 'Reef' . TextFormat::GOLD . 'Boot');
                $item->setLore(["使用時:スピードが2上がります(同じ効果は重複出来ません)", "", "ボーナス効果: ReefArmor",
                    "この効果を持つアイテムを4つ以上使用していた場合、スキル発動時のクールタイムが3秒減少します"]);
                break;
            default:
                return null;
        }
        $item->setUnbreakable();

        $nbt = $item->getNamedTag();
        $nbt->setString("owner", $xuid);
        $item->setNamedTag($nbt);

        return $item;
    }

    static function registerItems(): void
    {
        foreach ([self::PICKAXE, self::SHOVEL, self::AXE, self::HOE, self::HELMET, self::CHEST_PLATE, self::LEGGINGS, self::BOOTS] as $key) {
            CreativeInventory::getInstance()->add(self::getItem(0, $key));
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

        Christmas2021ReefItems::registerItems();
    }
}
