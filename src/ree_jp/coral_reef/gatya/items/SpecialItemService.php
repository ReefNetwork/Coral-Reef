<?php

namespace ree_jp\coral_reef\gatya\items;

use pocketmine\item\Item;

class SpecialItemService
{
    static function getRenewItem(string $xuid, string $item, int $durable): ?Item
    {
        switch ($item) {
            case ReefItems::PICKAXE:
            case ReefItems::SHOVEL:
            case ReefItems::AXE:
            case ReefItems::HOE:
            case ReefItems::HELMET:
            case ReefItems::CHEST_PLATE:
            case ReefItems::LEGGINGS:
            case ReefItems::BOOTS:
                return ReefItems::getItem($xuid, $item, $durable);
            case UltimateItems::PICKAXE:
            case UltimateItems::SHOVEL:
            case UltimateItems::AXE:
                return UltimateItems::getItem($xuid, $item, $durable);
            case SuperItems::PICKAXE:
            case SuperItems::SHOVEL:
                return SuperItems::getItem($xuid, $item, $durable);
            case RareItems::PICKAXE:
            case RareItems::SHOVEL:
                return RareItems::getItem($xuid, $item, $durable);
            default:
                return null;
        }
    }
}
