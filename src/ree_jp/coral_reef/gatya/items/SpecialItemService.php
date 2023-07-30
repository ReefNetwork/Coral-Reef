<?php

namespace ree_jp\coral_reef\gatya\items;

use pocketmine\item\Item;
use ree_jp\coral_reef\account\AccountStore;
use ree_jp\coral_reef\gatya\items\event\AtomicReefItem;
use ree_jp\coral_reef\gatya\items\event\Christmas2021ReefItems;
use ree_jp\coral_reef\gatya\items\event\HalloweenNightItems;
use ree_jp\coral_reef\gatya\items\event\HalloweenPartyItems;
use ree_jp\coral_reef\gatya\items\event\SnowCandyItem;
use ree_jp\coral_reef\gatya\items\event\SteamPunkItems;
use ree_jp\coral_reef\gatya\items\event\Summer2022ReefItems;

class SpecialItemService
{
    static function getRenewItem(string $xuid, string $item, int $durable, int $count, ?AccountStore $store): ?Item
    {
        return match ($item) {
            ConvertItems::NORMAL_TICKETS_FRAGMENT
            => ConvertItems::getItem($xuid, $item, $durable)->setCount($count),

            ReefItems::PICKAXE, ReefItems::SHOVEL, ReefItems::AXE, ReefItems::HOE, ReefItems::SWORD,
            ReefItems::HELMET, ReefItems::CHEST_PLATE, ReefItems::LEGGINGS, ReefItems::BOOTS
            => self::setOwner(ReefItems::getItem($xuid, $item, $durable), $store)->setCount($count),

            UltimateItems::PICKAXE, UltimateItems::SHOVEL, UltimateItems::AXE
            => UltimateItems::getItem($xuid, $item, $durable)->setCount($count),

            SuperItems::PICKAXE, SuperItems::SHOVEL
            => SuperItems::getItem($xuid, $item, $durable)->setCount($count),

            RareItems::PICKAXE, RareItems::SHOVEL
            => RareItems::getItem($xuid, $item, $durable)->setCount($count),

            Christmas2021ReefItems::PICKAXE, Christmas2021ReefItems::SHOVEL, Christmas2021ReefItems::AXE, Christmas2021ReefItems::HOE
            => self::setOwner(Christmas2021ReefItems::getItem($xuid, $item, $durable), $store)->setCount($count),

            Summer2022ReefItems::PICKAXE, Summer2022ReefItems::SHOVEL, Summer2022ReefItems::AXE, Summer2022ReefItems::HOE
            => self::setOwner(Summer2022ReefItems::getItem($xuid, $item, $durable), $store)->setCount($count),

            HalloweenNightItems::PICKAXE, HalloweenNightItems::SHOVEL, HalloweenNightItems::AXE, HalloweenNightItems::HOE
            => self::setOwner(HalloweenNightItems::getItem($xuid, $item, $durable), $store)->setCount($count),

            HalloweenPartyItems::PICKAXE, HalloweenPartyItems::SHOVEL, HalloweenPartyItems::AXE, HalloweenPartyItems::HOE
            => self::setOwner(HalloweenPartyItems::getItem($xuid, $item, $durable), $store)->setCount($count),

            SnowCandyItem::PICKAXE, SnowCandyItem::SHOVEL, SnowCandyItem::AXE, SnowCandyItem::HOE,
            => self::setOwner(SnowCandyItem::getItem($xuid, $item, $durable), $store)->setCount($count),

            SteamPunkItems::PICKAXE, SteamPunkItems::SHOVEL, SteamPunkItems::AXE, SteamPunkItems::HOE,
            => self::setOwner(SteamPunkItems::getItem($xuid, $item, $durable), $store)->setCount($count),

            AtomicReefItem::PICKAXE, AtomicReefItem::SHOVEL, AtomicReefItem::AXE, AtomicReefItem::HOE,
            => self::setOwner(AtomicReefItem::getItem($xuid, $item, $durable), $store)->setCount($count),

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
