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

use pocketmine\block\BlockLegacyIds;
use pocketmine\inventory\CreativeInventory;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\enchantment\VanillaEnchantments;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;
use pocketmine\utils\TextFormat;
use ree_jp\coral_reef\gatya\items\ReefItems;
use ree_jp\coral_reef\skill\TreeBreakService;

class Christmas2021ReefItems extends ReefItems
{
    const PICKAXE = "christmas_2021_reef_pickaxe";
    const SHOVEL = "christmas_2021_reef_shovel";
    const AXE = "christmas_2021_reef_axe";
    const HOE = "christmas_2021_reef_hoe";

    static function registerItems(): void
    {
        foreach ([self::PICKAXE, self::SHOVEL, self::AXE, self::HOE] as $key) {
            CreativeInventory::getInstance()->add(self::getItem(0, $key));
        }
    }

    static function getItem(string $xuid, string $type, int $durable = 0): ?Item
    {
        switch ($type) {
            case self::PICKAXE:
                $item = VanillaItems::DIAMOND_PICKAXE();
                $nbt = $item->getNamedTag();
                $nbt->setString(self::REEF_SP_ITEM, self::PICKAXE);
                $item->setNamedTag($nbt);
                $item->setCustomName(TextFormat::DARK_GREEN . "Christmas" . TextFormat::RED . "2021" .
                    TextFormat::GREEN . 'Reef' . TextFormat::GOLD . 'Pickaxe');
                $item->addEnchantment(new EnchantmentInstance(VanillaEnchantments::EFFICIENCY(), 10));
                $item->addEnchantment(new EnchantmentInstance(VanillaEnchantments::SILK_TOUCH(), 10));
                break;
            case self::SHOVEL:
                $item = VanillaItems::DIAMOND_SHOVEL();
                $nbt = $item->getNamedTag();
                $nbt->setString(self::REEF_SP_ITEM, self::SHOVEL);
                $nbt->setByte(TreeBreakService::TREE_CUT, 1);
                $item->setNamedTag($nbt);
                $item->setCustomName(TextFormat::DARK_GREEN . "Christmas" . TextFormat::RED . "2021" .
                    TextFormat::GREEN . 'Reef' . TextFormat::GOLD . 'Shovel');
                $item->addEnchantment(new EnchantmentInstance(VanillaEnchantments::EFFICIENCY(), 10));
                $item->addEnchantment(new EnchantmentInstance(VanillaEnchantments::SILK_TOUCH(), 10));
                $item->setLore(["使用時:木を一括破壊します"]);
                break;
            case self::AXE:
                $item = VanillaItems::DIAMOND_AXE();
                $nbt = $item->getNamedTag();
                $nbt->setString(self::REEF_SP_ITEM, self::AXE);
                $item->setNamedTag($nbt);
                $item->setCustomName(TextFormat::DARK_GREEN . "Christmas" . TextFormat::RED . "2021" .
                    TextFormat::GREEN . 'Reef' . TextFormat::GOLD . 'Axe');
                $item->addEnchantment(new EnchantmentInstance(VanillaEnchantments::EFFICIENCY(), 10));
                $item->addEnchantment(new EnchantmentInstance(VanillaEnchantments::SILK_TOUCH(), 10));
                break;
            case self::HOE:
                $item = VanillaItems::DIAMOND_HOE();
                $nbt = $item->getNamedTag();
                $nbt->setString(self::REEF_SP_ITEM, self::HOE);
                $item->setNamedTag($nbt);
                $item->setCustomName(TextFormat::DARK_GREEN . "Christmas" . TextFormat::RED . "2021" .
                    TextFormat::GREEN . 'Reef' . TextFormat::GOLD . 'Hoe');
                $item->addEnchantment(new EnchantmentInstance(VanillaEnchantments::EFFICIENCY(), 10));
                $item->addEnchantment(new EnchantmentInstance(VanillaEnchantments::SILK_TOUCH(), 10));
                break;
            default:
                return null;
        }
        $item->setUnbreakable();

        $lore = $item->getLore();
        $lore[] = "使用時:水を凍らすブロックが雪に変わります";
        $item->setLore($lore);

        $nbt = $item->getNamedTag();
        $nbt->setInt("frozen_block", BlockLegacyIds::SNOW_BLOCK);
        $nbt->setString("owner", $xuid);
        $item->setNamedTag($nbt);

        return $item;
    }
}
