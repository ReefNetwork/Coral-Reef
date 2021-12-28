<?php

namespace ree_jp\coral_reef\gatya\items;

use pocketmine\item\Item;
use ree_jp\coral_reef\account\AccountStore;
use ree_jp\coral_reef\gatya\items\event\Christmas2021ReefItems;

class SpecialItemService
{
    static function getRenewItem(string $xuid, string $item, int $durable, ?AccountStore $store): ?Item
    {
        return match ($item) {
            ConvertItems::NORMAL_TICKETS_FRAGMENT
            => ConvertItems::getItem($xuid, $item, $durable),

            ReefItems::PICKAXE, ReefItems::SHOVEL, ReefItems::AXE, ReefItems::HOE, ReefItems::HELMET, ReefItems::CHEST_PLATE, ReefItems::LEGGINGS, ReefItems::BOOTS
            => self::setOwner(ReefItems::getItem($xuid, $item, $durable), $store),

            UltimateItems::PICKAXE, UltimateItems::SHOVEL, UltimateItems::AXE
            => UltimateItems::getItem($xuid, $item, $durable),

            SuperItems::PICKAXE, SuperItems::SHOVEL
            => SuperItems::getItem($xuid, $item, $durable),

            RareItems::PICKAXE, RareItems::SHOVEL
            => RareItems::getItem($xuid, $item, $durable),

            Christmas2021ReefItems::PICKAXE, Christmas2021ReefItems::SHOVEL, Christmas2021ReefItems::AXE, Christmas2021ReefItems::HOE
            => Christmas2021ReefItems::getItem($xuid, $item, $durable),

            default => null,
        };
    }

    static function setOwner(Item $item, ?AccountStore $store): ?Item
    {
        if (!is_null($store)) {
            $lore = $item->getLore();
            $nbt = $item->getNamedTag();
            $lore[] = "所有者: " . $store->getUserName($nbt->getString("owner"));
            $item->setLore($lore);
        }
        return $item;
    }
}
