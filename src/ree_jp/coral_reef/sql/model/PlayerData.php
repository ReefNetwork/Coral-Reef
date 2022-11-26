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

use pocketmine\color\Color;
use pocketmine\entity\effect\Effect;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\item\Item;
use pocketmine\lang\Translatable;
use pocketmine\player\Player;
use ree_jp\coral_reef\account\AccountStore;
use ree_jp\coral_reef\gatya\items\ReefItems;
use ree_jp\coral_reef\gatya\items\SpecialItemService;
use ree_jp\coral_reef\StoreHouse;

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

    public function renewItems(StoreHouse $store, string $xuid): void
    {
        /** @var AccountStore $accountStore */
        $accountStore = $store->get(AccountStore::class);
        foreach ($this->inv as $key => $item) {
            $this->inv[$key] = $this->renew($item, $accountStore, $xuid);
        }
        foreach ($this->armorInv as $key => $item) {
            $this->armorInv[$key] = $this->renew($item, $accountStore, $xuid);
        }
        foreach ($this->enderInv as $key => $item) {
            $this->enderInv[$key] = $this->renew($item, $accountStore, $xuid);
        }
    }

    private function renew(Item $item, AccountStore $store, string $xuid): Item
    {
        $nbt = $item->getNamedTag();
        $renewed = SpecialItemService::getRenewItem($nbt->getString("owner", $xuid), $nbt->getString(ReefItems::REEF_SP_ITEM, "unknown"),
            $item->getMeta(), $item->getCount(), $store);
        if ($renewed != null) return $renewed;
        return $item;
    }

    static function create(Player $p): self
    {
        return new PlayerData($p->getXuid(), $p->getInventory()->getContents(), $p->getArmorInventory()->getContents(), $p->getOffHandInventory()->getContents(),
            $p->getEnderInventory()->getContents(), $p->getEffects()->all(), $p->getHealth(), $p->getHungerManager()->getFood(), $p->getXpManager()->getCurrentTotalXp());
    }

    static function jsonToItems(?string $json): array
    {
        if ($json == null) return [];
        $content = [];
        foreach (json_decode($json, true) as $slot => $item) {
            $content[$slot] = Item::jsonDeserialize($item);
        }
        return $content;
    }

    static function jsonToEffect(?string $json): array
    {
        if ($json == null) return [];
        $content = [];
        foreach (json_decode($json, true) as $effect) {
            $effectType = self::getEffect($effect["name"]);
            if ($effectType == null) continue;

            $content[] = new EffectInstance($effectType, $effect["duration"], $effect["amplifier"], $effect["visible"], $effect["ambient"],
                Color::fromARGB($effect["color"]));
        }
        return $content;
    }

    /**
     * @param EffectInstance[] $effects
     * @return string
     */
    static function effectToJson(array $effects): string
    {
        $array = [];
        foreach ($effects as $effect) {
            $name = $effect->getType()->getName();
            if ($name instanceof Translatable) $name = $name->getText();
            $array[] = ["name" => $name, "duration" => $effect->getDuration(), "amplifier" => $effect->getAmplifier(),
                "visible" => $effect->isVisible(), "ambient" => $effect->isAmbient(), "color" => $effect->getColor()->toARGB()];
        }
        return json_encode($array);
    }

    private static function getEffect(string $name): ?Effect
    {
        $all = VanillaEffects::getAll();
        foreach ($all as $effect) {
            $effectName = $effect->getName();
            if ($effectName instanceof Translatable) $effectName = $effectName->getText();
            if ($effectName == $name) return $effect;
        }
        return null;
    }
}
