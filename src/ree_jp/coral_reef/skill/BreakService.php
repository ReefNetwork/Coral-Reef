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
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\item\LegacyStringToItemParser;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\world\format\Chunk;
use pocketmine\world\particle\BlockBreakParticle;
use pocketmine\world\World;
use ree_jp\coral_reef\account\SettingManager;
use ree_jp\coral_reef\sql\SettingConst;

class BreakService
{
    static function breakBlockBySkill(Player $p, Block $bl, array &$store): void
    {
        $hand = $p->getInventory()->getItemInHand();
        self::frozeWater($p, $bl->getPosition(), $hand, $store);

        if ($bl->getBreakInfo()->getHardness() < 0 || $bl instanceof Liquid) return;

        self::silentBreak($p->getWorld(), $bl, $hand, $p);
    }

    static function frozeWater(Player $p, Vector3 $vec, Item $hand, array &$store): void
    {
        if (!SettingManager::isEnableOption($p->getXuid(), SettingConst::NO_FREEZE_WATER)) {
            $nbt = $hand->getNamedTag();
            $id = $nbt->getInt("frozen_block", 0);


            if ($id === 0) {
                $block = BlockFactory::getInstance()->get(BlockLegacyIds::STAINED_GLASS, 3);
            } else {
                $block = BlockFactory::getInstance()->get($id, 0);
            }

            self::changeWater($p->getWorld(), $vec, $block, $store);
            self::changeWater($p->getWorld(), $vec->add(0, 1, 0), $block, $store);
            self::changeWater($p->getWorld(), $vec->add(0, -1, 0), $block, $store);
            self::changeWater($p->getWorld(), $vec->add(1, 0, 0), $block, $store);
            self::changeWater($p->getWorld(), $vec->add(-1, 0, 0), $block, $store);
            self::changeWater($p->getWorld(), $vec->add(0, 0, 1), $block, $store);
            self::changeWater($p->getWorld(), $vec->add(0, 0, -1), $block, $store);
        }
    }

    /**
     * @param World|null $level
     * @param Vector3 $vec3
     * @param Block $replaceBlock
     * @param Vector3[] $store
     * @return void
     */
    private static function changeWater(?World $level, Vector3 $vec3, Block $replaceBlock, array &$store): void // 水を水色のガラスに変える
    {
        if (is_null($level) || in_array($vec3, $store)) return;
        $store[] = $vec3;
        $checkId = $level->getBlock($vec3)->getId();
        if (($checkId === BlockLegacyIds::WATER) || ($checkId === BlockLegacyIds::FLOWING_WATER)) { // 水を水色のガラスに変える
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

        $xp = 0;
        if ($p !== null and $p->hasFiniteResources()) {
            $xp = array_sum(array_map(fn(Block $block) => $block->getXpDropForTool($item), $affectedBlocks));
        }

        if ($p !== null) {
            $ev = new BlockBreakEvent($p, $bl, $item, $p->isCreative(), $drops, $xp);

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

            $xp = $ev->getXpDropAmount();

        } elseif (!$bl->getBreakInfo()->isBreakable()) {
            return;
        }

        foreach ($affectedBlocks as $t) {
            $world->addParticle($t->getPosition()->add(0.5, 0.5, 0.5), new BlockBreakParticle($t));
            $tile = $world->getTile($t->getPosition());
            $tile?->onBlockDestroyed();

            $world->setBlock($t->getPosition(), VanillaBlocks::AIR(), false);
        }

        $item->onDestroyBlock($bl);

        if ($xp > 0) {
            $p->getXpManager()->addXp($xp);
        }
    }

    static function updateBlock(World $world, Block $bl): void
    {
        $world->setBlock($bl->getPosition(), $bl);
    }
}
