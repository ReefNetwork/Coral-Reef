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
use pocketmine\block\BlockLegacyIds;
use pocketmine\block\Liquid;
use pocketmine\block\VanillaBlocks;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockUpdateEvent;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\item\LegacyStringToItemParser;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\world\format\Chunk;
use pocketmine\world\particle\BlockBreakParticle;
use pocketmine\world\World;
use ree_jp\coral_reef\account\SettingManager;
use ree_jp\coral_reef\sql\SettingConst;

class BreakService
{
    static function breakBlockBySkill(Player $p, Block $bl): void
    {
        $hand = $p->getInventory()->getItemInHand();
        self::frozeWater($p, $bl->getPosition(), $hand);

        if ($bl->getBreakInfo()->getHardness() < 0 || $bl instanceof Liquid) return;

        self::silentBreak($p->getWorld(), $bl, $hand, $p);
    }

    static function frozeWater(Player $p, Vector3 $vec, Item $hand): void
    {
        if (!SettingManager::isEnableOption($p->getXuid(), SettingConst::NO_FREEZE_WATER)) {
            $nbt = $hand->getNamedTag();
            $id = $nbt->getInt("frozen_block", 0);


            if ($id === 0) {
                $block = BlockFactory::getInstance()->get(BlockLegacyIds::STAINED_GLASS, 3);
            } else {
                $block = BlockFactory::getInstance()->get($id, 0);
            }

            self::changeWater($p->getWorld(), $vec, $block);
            self::changeWater($p->getWorld(), $vec->add(0, 1, 0), $block);
            self::changeWater($p->getWorld(), $vec->add(0, -1, 0), $block);
            self::changeWater($p->getWorld(), $vec->add(1, 0, 0), $block);
            self::changeWater($p->getWorld(), $vec->add(-1, 0, 0), $block);
            self::changeWater($p->getWorld(), $vec->add(0, 0, 1), $block);
            self::changeWater($p->getWorld(), $vec->add(0, 0, -1), $block);
        }
    }

    private static function changeWater(?World $level, Vector3 $vec3, Block $replaceBlock): void // 水を水色のガラスに変える
    {
        if (is_null($level)) return;
        if ($level->getBlock($vec3)->getId() === BlockLegacyIds::WATER) { // 水を水色のガラスに変える
            $level->setBlock($vec3, $replaceBlock, false);
        }
    }

    /** @noinspection DuplicatedCode */
    private static function silentBreak(World $world, Block $bl, Item $item = null, Player $p = null): void
    {
        $vector = $bl->getPosition()->floor();

        $chunkX = $vector->getFloorX() >> Chunk::COORD_BIT_SIZE;
        $chunkZ = $vector->getFloorZ() >> Chunk::COORD_BIT_SIZE;
        if (!$world->isChunkLoaded($chunkX, $chunkZ) || $world->isChunkLocked($chunkX, $chunkZ)) {
            return;
        }

        $affectedBlocks = $bl->getAffectedBlocks();

        if ($item === null) {
            $item = ItemFactory::air();
        }

        $drops = [];
        if ($p === null or $p->hasFiniteResources()) {
            $drops = array_merge(...array_map(fn(Block $block) => $block->getDrops($item), $affectedBlocks));
        }

        $xpDrop = 0;
        if ($p !== null and $p->hasFiniteResources()) {
            $xpDrop = array_sum(array_map(fn(Block $block) => $block->getXpDropForTool($item), $affectedBlocks));
        }

        if ($p !== null) {
            $ev = new BlockBreakEvent($p, $bl, $item, $p->isCreative(), $drops, $xpDrop);

            if ($bl instanceof Air or ($p->isSurvival() and !$bl->getBreakInfo()->isBreakable()) or $p->isSpectator()) {
                $ev->cancel();
            }

            if ($p->isAdventure(true) and !$ev->isCancelled()) {
                $canBreak = false;
                $itemParser = LegacyStringToItemParser::getInstance();
                foreach ($item->getCanDestroy() as $v) {
                    $entry = $itemParser->parse($v);
                    if ($entry->getBlock()->isSameType($bl)) {
                        $canBreak = true;
                        break;
                    }
                }

                if (!$canBreak) {
                    $ev->cancel();
                }
            }

            $ev->call();
            if ($ev->isCancelled()) {
                return;
            }

            $drops = $ev->getDrops();
            $xpDrop = $ev->getXpDropAmount();

        } elseif (!$bl->getBreakInfo()->isBreakable()) {
            return;
        }

        foreach ($affectedBlocks as $t) {
            $world->addParticle($t->getPosition()->add(0.5, 0.5, 0.5), new BlockBreakParticle($t));
            $t->onBreak($item, $p);
            $world->setBlock($t->getPosition(), VanillaBlocks::AIR(), false);

            $tile = $world->getTile($t->getPosition());
            $tile?->onBlockDestroyed();
        }

        $item->onDestroyBlock($bl);

        if (count($drops) > 0) {
            $dropPos = $vector->add(0.5, 0.5, 0.5);
            foreach ($drops as $drop) {
                if (!$drop->isNull()) {
                    $world->dropItem($dropPos, $drop);
                }
            }
        }

        if ($xpDrop > 0) {
            $world->dropExperience($vector->add(0.5, 0.5, 0.5), $xpDrop);
        }
    }

    static function updateBlock(World $world, Block $bl): void
    {
        /** @noinspection DuplicatedCode */
        $world->updateAllLight($bl->getPosition()->getFloorX(), $bl->getPosition()->getFloorY(), $bl->getPosition()->getFloorZ());

        $ev = new BlockUpdateEvent($bl);
        $ev->call();
        if (!$ev->isCancelled()) {
            foreach ($world->getNearbyEntities(AxisAlignedBB::one()->offset($bl->getPosition()->getFloorX(), $bl->getPosition()->getFloorY(),
                $bl->getPosition()->getFloorZ())) as $entity) {
                $entity->onNearbyBlockChange();
            }
            $ev->getBlock()->onNearbyBlockChange();
        }
    }
}
