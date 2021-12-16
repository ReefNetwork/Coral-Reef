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

namespace ree_jp\coral_reef\skill;

use pocketmine\block\Air;
use pocketmine\block\Block;
use pocketmine\block\BlockFactory;
use pocketmine\block\BlockIds;
use pocketmine\block\Ice;
use pocketmine\block\Liquid;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockUpdateEvent;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\level\Level;
use pocketmine\level\particle\DestroyBlockParticle;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\Player;
use pocketmine\tile\Chest;
use pocketmine\tile\Container;
use ree_jp\coral_reef\account\SettingManager;
use ree_jp\coral_reef\sql\SettingConst;

class BreakService
{
    static function breakBlockBySkill(Player $p, Block $bl): void
    {
        $hand = $p->getInventory()->getItemInHand();
        self::frozeWater($p, $bl, $hand);

        if ($bl->getHardness() < 0 || $bl instanceof Liquid) return;

        self::silentBreak($p->getLevel(), $bl, $hand, $p);
    }

    static function frozeWater(Player $p, Vector3 $vec, Item $hand): void
    {
        if (!SettingManager::isEnableOption($p->getXuid(), SettingConst::NO_FREEZE_WATER)) {
            $nbt = $hand->getNamedTagEntry("frozen_block");
            if ($nbt instanceof IntTag) {
                $block = Block::get($nbt->getValue());
            } else {
                $block = Block::get(BlockIds::STAINED_GLASS, 3);
            }

            self::changeWater($p->getLevel(), $vec, $block);
            self::changeWater($p->getLevel(), $vec->add(0, 1), $block);
            self::changeWater($p->getLevel(), $vec->add(0, -1), $block);
            self::changeWater($p->getLevel(), $vec->add(1), $block);
            self::changeWater($p->getLevel(), $vec->add(-1), $block);
            self::changeWater($p->getLevel(), $vec->add(0, 0, 1), $block);
            self::changeWater($p->getLevel(), $vec->add(0, 0, -1), $block);
        }
    }

    private static function changeWater(?Level $level, Vector3 $vec3, Block $replaceBlock): void // 水を水色のガラスに変える
    {
        if (is_null($level)) return;
        if ($level->getBlock($vec3)->getId() === BlockIds::WATER) { // 水を水色のガラスに変える
            $level->setBlock($vec3, $replaceBlock);
        }
    }

    /** @noinspection DuplicatedCode */
    private static function silentBreak(Level $level, Block $bl, Item $item = null, Player $p = null): void
    {
        $affectedBlocks = $bl->getAffectedBlocks();
        if ($item === null) $item = ItemFactory::get(BlockIds::AIR, 0, 0);

        $drops = [];
        if ($p === null or !$p->isCreative()) {
            $drops = array_merge(...array_map(function (Block $block) use ($item): array {
                return $block->getDrops($item);
            }, $affectedBlocks));
        }

        $xpDrop = 0;
        if ($p !== null and !$p->isCreative()) {
            $xpDrop = array_sum(array_map(function (Block $block) use ($item): int {
                return $block->getXpDropForTool($item);
            }, $affectedBlocks));
        }

        if ($p !== null) {
            $ev = new BlockBreakEvent($p, $bl, $item, $p->isCreative(), $drops, $xpDrop);

            if ($bl instanceof Air or ($p->isSurvival() and !$bl->isBreakable($item)) or $p->isSpectator()) {
                $ev->setCancelled();
            } elseif ($level->checkSpawnProtection($p, $bl)) {
                $ev->setCancelled(); //set it to cancelled so plugins can bypass this
            }

            if ($p->isAdventure(true) and !$ev->isCancelled()) {
                $tag = $item->getNamedTagEntry("CanDestroy");
                $canBreak = false;
                if ($tag instanceof ListTag) {
                    foreach ($tag as $v) {
                        if ($v instanceof StringTag) {
                            $entry = ItemFactory::fromStringSingle($v->getValue());
                            if ($entry->getId() > 0 and $entry->getBlock()->getId() === $bl->getId()) {
                                $canBreak = true;
                                break;
                            }
                        }
                    }
                }
                $ev->setCancelled(!$canBreak);
            }

            $ev->call();
            if ($ev->isCancelled()) {
                return;
            }

            $drops = $ev->getDrops();
            $xpDrop = $ev->getXpDropAmount();

        } elseif (!$bl->isBreakable($item)) {
            return;
        }

        foreach ($affectedBlocks as $t) {
            $level->addParticle(new DestroyBlockParticle($t->add(0.5, 0.5, 0.5), $t));
            if ($t instanceof Ice && $p->isSurvival() && !$item->hasEnchantment(Enchantment::SILK_TOUCH)) { // 氷をはシルクタッチで取らないと水にする
                $level->setBlock($t, BlockFactory::get(BlockIds::STAINED_GLASS, 3), false, false);
            } else {
                $level->setBlock($t, BlockFactory::get(BlockIds::AIR), false, false);
            }

            $tile = $level->getTile($t);
            if ($tile !== null) {
                if ($tile instanceof Container) {
                    if ($tile instanceof Chest) {
                        $tile->unpair();
                    }
                    $tile->getInventory()->dropContents($level, $p);
                }
                $tile->close();
            }
        }

        $item->onDestroyBlock($bl);

        if (count($drops) > 0) {
            $dropPos = $bl->add(0.5, 0.5, 0.5);
            foreach ($drops as $drop) {
                if (!$drop->isNull()) {
                    $level->dropItem($dropPos, $drop);
                }
            }
        }

        if ($xpDrop > 0) {
            $level->dropExperience($bl->add(0.5, 0.5, 0.5), $xpDrop);
        }
    }

    static function updateBlock(Level $level, Block $bl): void
    {
        /** @noinspection DuplicatedCode */
        $level->updateAllLight($bl);

        $ev = new BlockUpdateEvent($bl);
        $ev->call();
        if (!$ev->isCancelled()) {
            foreach ($level->getNearbyEntities(new AxisAlignedBB($bl->x - 1, $bl->y - 1, $bl->z - 1, $bl->x + 2,
                $bl->y + 2, $bl->z + 2)) as $entity) {
                $entity->onNearbyBlockChange();
            }
            $ev->getBlock()->onNearbyBlockChange();
            $level->scheduleNeighbourBlockUpdates($bl);
        }
    }
}
