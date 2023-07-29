<?php
/*
 *  CCCCC                        lll RRRRRR                 fff
 * CC    C  oooo  rr rr    aa aa lll RR   RR   eee    eee  ff
 * CC      oo  oo rrr  r  aa aaa lll RRRRRR  ee   e ee   e ffff
 * CC    C oo  oo rr     aa  aaa lll RR  RR  eeeee  eeeee  ff
 *  CCCCC   oooo  rr      aaa aa lll RR   RR  eeeee  eeeee ff
 *
 * Copyright (c) 2023. Ree-jp(https://ree-jp.net)
 */

namespace ree_jp\coral_reef\gatya\items\event;

use pocketmine\inventory\CreativeInventory;
use pocketmine\item\Durable;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\enchantment\VanillaEnchantments;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\utils\TextFormat;
use ree_jp\coral_reef\gatya\items\CustomItemIDs;
use ree_jp\coral_reef\gatya\items\ReefItems;
use ree_jp\coral_reef\skill\TreeBreakService;

class AtomicReefItem extends ReefItems
{
    const PICKAXE = "atomic_pickaxe";
    const SHOVEL = "atomic_shovel";
    const AXE = "atomic_axe";
    const HOE = "atomic_hoe";

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
                $item = ItemFactory::getInstance()->get(CustomItemIDs::ATOMIC_PICKAXE);
                $nbt = $item->getNamedTag();
                $nbt->setString(self::REEF_SP_ITEM, self::PICKAXE);
                $item->setNamedTag($nbt);
                $item->setCustomName(TextFormat::AQUA . "Atomic" .
                    TextFormat::GREEN . 'Reef' . TextFormat::GOLD . 'Pickaxe');
                $item->addEnchantment(new EnchantmentInstance(VanillaEnchantments::EFFICIENCY(), 10));
                $item->addEnchantment(new EnchantmentInstance(VanillaEnchantments::SILK_TOUCH(), 10));
                break;
            case self::SHOVEL:
                $item = ItemFactory::getInstance()->get(CustomItemIDs::ATOMIC_SHOVEL);
                $nbt = $item->getNamedTag();
                $nbt->setString(self::REEF_SP_ITEM, self::SHOVEL);
                $item->setNamedTag($nbt);
                $item->setCustomName(TextFormat::AQUA . "Atomic" .
                    TextFormat::GREEN . 'Reef' . TextFormat::GOLD . 'Shovel');
                $item->addEnchantment(new EnchantmentInstance(VanillaEnchantments::EFFICIENCY(), 10));
                $item->addEnchantment(new EnchantmentInstance(VanillaEnchantments::SILK_TOUCH(), 10));
                break;
            case self::AXE:
                $item = ItemFactory::getInstance()->get(CustomItemIDs::ATOMIC_AXE);
                $nbt = $item->getNamedTag();
                $nbt->setString(self::REEF_SP_ITEM, self::AXE);
                $nbt->setByte(TreeBreakService::TREE_CUT, 1);
                $item->setNamedTag($nbt);
                $item->setCustomName(TextFormat::AQUA . "Atomic" .
                    TextFormat::GREEN . 'Reef' . TextFormat::GOLD . 'Axe');
                $item->addEnchantment(new EnchantmentInstance(VanillaEnchantments::EFFICIENCY(), 10));
                $item->addEnchantment(new EnchantmentInstance(VanillaEnchantments::SILK_TOUCH(), 10));
                $item->setLore(["スキル発動時:木を一括破壊します"]);
                break;
            case self::HOE:
                $item = ItemFactory::getInstance()->get(CustomItemIDs::ATOMIC_HOE);
                $nbt = $item->getNamedTag();
                $nbt->setString(self::REEF_SP_ITEM, self::HOE);
                $item->setNamedTag($nbt);
                $item->setCustomName(TextFormat::AQUA . "Atomic" .
                    TextFormat::GREEN . 'Reef' . TextFormat::GOLD . 'Hoe');
                $item->addEnchantment(new EnchantmentInstance(VanillaEnchantments::EFFICIENCY(), 10));
                $item->addEnchantment(new EnchantmentInstance(VanillaEnchantments::SILK_TOUCH(), 10));
                break;
            default:
                return null;
        }
        var_dump($item);
        if (!$item instanceof Durable) return null;

        var_dump("c");
        $item->setUnbreakable();
        $nbt = $item->getNamedTag();
        $nbt->setString("owner", $xuid);
        $item->setNamedTag($nbt);
        return $item;
    }
}
