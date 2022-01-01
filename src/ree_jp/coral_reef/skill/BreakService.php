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
use pocketmine\block\Ice;
use pocketmine\block\Liquid;
use pocketmine\block\tile\Chest;
use pocketmine\block\tile\Container;
use pocketmine\block\VanillaBlocks;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockUpdateEvent;
use pocketmine\item\enchantment\VanillaEnchantments;
use pocketmine\item\Item;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\world\particle\BlockBreakParticle;
use pocketmine\world\World;
use ree_jp\coral_reef\account\SettingManager;
use ree_jp\coral_reef\sql\SettingConst;

class BreakService
{
    static function breakBlockBySkill(Player $p, Block $bl): void
    {
        var_dump("aaa");
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
    private static function silentBreak(World $level, Block $bl, Item $item = null, Player $p = null): void
    {
        $affectedBlocks = $bl->getAffectedBlocks();
        if ($item === null) $item = VanillaBlocks::AIR();

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

            if ($bl instanceof Air or $p->isSurvival() or $p->isSpectator()) {
                $ev->cancel();
            }
            if ($p->isAdventure(true) and !$ev->isCancelled()) {
                $ev->cancel();
            }

            $ev->call();
            if ($ev->isCancelled()) {
                var_dump("cancelled");
                return;
            }

            $drops = $ev->getDrops();
            $xpDrop = $ev->getXpDropAmount();

        }

        foreach ($affectedBlocks as $t) {
            $level->addParticle($t->getPosition()->add(0.5, 0.5, 0.5), new BlockBreakParticle($t));
            if ($t instanceof Ice && $p->isSurvival() && !$item->hasEnchantment(VanillaEnchantments::SILK_TOUCH())) { // 氷をはシルクタッチで取らないと水にする
                $level->setBlock($t->getPosition(), BlockFactory::getInstance()->get(BlockLegacyIds::STAINED_GLASS, 3), false);
            } else {
                $level->setBlock($t->getPosition(), VanillaBlocks::AIR(), false);
            }

            $tile = $level->getTile($t->getPosition());
            if ($tile !== null) {
                if ($tile instanceof Container) {
                    if ($tile instanceof Chest) {
                        $tile->unpair();
                    }
                    foreach ($tile->getInventory()->getContents() as $item) {
                        $level->dropItem($t->getPosition(), $item);
                    }
                }
                $tile->close();
            }
        }

        $item->onDestroyBlock($bl);

        if (count($drops) > 0) {
            $dropPos = $bl->getPosition()->add(0.5, 0.5, 0.5);
            foreach ($drops as $drop) {
                if (!$drop->isNull()) {
                    $level->dropItem($dropPos, $drop);
                }
            }
        }

        if ($xpDrop > 0) {
            $level->dropExperience($bl->getPosition()->add(0.5, 0.5, 0.5), $xpDrop);
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
