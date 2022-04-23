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

namespace ree_jp\coral_reef\item;

use pocketmine\block\Block;
use pocketmine\item\Item;
use pocketmine\item\ItemUseResult;
use pocketmine\math\Vector3;
use pocketmine\player\Player;

abstract class ClickItem extends Item
{
    protected static function markCoolTime(Player $p, ClickItem $item): bool
    {
        if (!$p->hasItemCooldown($item)) {
            $p->resetItemCooldown($item, 10);
            return true;
        }
        return false;
    }

    public function onInteractBlock(Player $player, Block $blockReplace, Block $blockClicked, int $face, Vector3 $clickVector): ItemUseResult
    {
        return static::onActive($player, $this);
    }

    abstract static function onActive(Player $p, ClickItem $item): ItemUseResult;

    public function onClickAir(Player $player, Vector3 $directionVector): ItemUseResult
    {
        return static::onActive($player, $this);
    }

    public function active(Player $player): ItemUseResult
    {
        return static::onActive($player, $this);
    }
}