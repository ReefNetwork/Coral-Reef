<?php
/*
 *  CCCCC                        lll RRRRRR                 fff
 * CC    C  oooo  rr rr    aa aa lll RR   RR   eee    eee  ff
 * CC      oo  oo rrr  r  aa aaa lll RRRRRR  ee   e ee   e ffff
 * CC    C oo  oo rr     aa  aaa lll RR  RR  eeeee  eeeee  ff
 *  CCCCC   oooo  rr      aaa aa lll RR   RR  eeeee  eeeee ff
 *
 * Copyright (c) 2022. Ree-jp(https://ree-jp.net)
 */

namespace ree_jp\coral_reef\sql\model;

use pocketmine\entity\effect\Effect;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\item\Item;
use pocketmine\player\Player;

class PlayerData
{
    /**
     * @param string $xuid
     * @param Item[] $inv
     * @param Item[] $armorInv
     * @param Item[] $offHandInv
     * @param Item[] $enderInv
     * @param EffectInstance[] $effects
     * @param int $health
     * @param float $hunger
     * @param int $xp
     */
    public function __construct(public string $xuid, public array $inv, public array $armorInv, public array $offHandInv,
                                public array  $enderInv, public array $effects, public int $health, public float $hunger, public int $xp)
    {
    }

    static function create(Player $p): self
    {
        return new PlayerData($p->getXuid(), $p->getInventory()->getContents(), $p->getArmorInventory()->getContents(), $p->getOffHandInventory()->getContents(),
            $p->getEnderInventory()->getContents(), $p->getEffects()->all(), $p->getHealth(), $p->getHungerManager()->getFood(), $p->getXpManager()->getCurrentTotalXp());
    }

    static function jsonToItems($json): array
    {
        $content = [];
        foreach (json_decode($json, true) as $slot => $item) {
            $content[$slot] = Item::jsonDeserialize($item);
        }
        return $content;
    }

    static function jsonToEffect($json): array
    {
        $content = [];
        foreach (json_decode($json, true) as $effect) {
            $effectType = self::getEffect($effect["effectType"]["name"]);
            if ($effectType == null) continue;

            $content[] = new EffectInstance($effectType, $effect["duration"], $effect["amplifier"], $effect["visible"], $effect["ambient"], $effect["overrideColor"]);
        }
        return $content;
    }

    private static function getEffect(string $name): ?Effect
    {
        $upper = strtoupper($name);
        $all = VanillaEffects::getAll();
        if (isset($all[$upper])) return $all[$upper];
        return null;
    }
}
