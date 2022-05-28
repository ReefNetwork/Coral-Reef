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
use pocketmine\block\Flowable;
use pocketmine\block\Liquid;
use pocketmine\block\VanillaBlocks;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\world\format\Chunk;
use pocketmine\world\World;
use ree_jp\coral_reef\account\SettingManager;
use ree_jp\coral_reef\account\UserAccount;
use ree_jp\coral_reef\land\LandService;
use ree_jp\coral_reef\land\LandStore;
use ree_jp\coral_reef\money\MoneyService;
use ree_jp\coral_reef\session\SessionData;
use ree_jp\coral_reef\sql\mysql\SQLRepository;
use ree_jp\coral_reef\sql\SettingConst;
use ree_jp\coral_reef\task\ServerUpdateTask;
use ree_jp\stackstorage\api\StackStorageAPI;

class BreakService
{
    static function breakBlockBySkill(SQLRepository $repo, LandStore $landStore, SessionData $session, Player $p, UserAccount $user, AxisAlignedBB $aabb,
                                      Vector3       $origin): void
    {
        var_dump($aabb);
        for ($i = 0; $i < 5; $i++) {
            for ($x = $aabb->minX; $x <= $aabb->maxX; $x += 0.6) {
                LandService::sendCheckSpaceEffect($p, $aabb, $x, $aabb->minZ);
                LandService::sendCheckSpaceEffect($p, $aabb, $x, $aabb->maxZ);
            }
            for ($z = $aabb->minZ; $z <= $aabb->maxZ; $z += 0.6) {
                LandService::sendCheckSpaceEffect($p, $aabb, $aabb->minX, $z);
                LandService::sendCheckSpaceEffect($p, $aabb, $aabb->maxX, $z);
            }
        }
        $lands = LandService::getDuplicateLand($landStore, $p->getWorld()->getFolderName(), $aabb);
        $hand = $p->getInventory()->getItemInHand();
        $isNoFreeze = SettingManager::isEnableOption($p->getXuid(), SettingConst::NO_FREEZE_WATER);
        $freezeBlock = self::getFreezeBlock($hand);
        $popupMessage = "";

        for ($nowX = $aabb->minX; $nowX <= $aabb->maxX; $nowX++) {
            for ($nowZ = $aabb->minZ; $nowZ <= $aabb->maxZ; $nowZ++) {
                $highVec3 = new Vector3($nowX, $aabb->maxY, $nowZ);
                $highCheck = self::highCheck($p, $highVec3);
                if (!$highCheck) {
                    $popupMessage = "上から掘ってください";
                    continue;
                }
                foreach ($lands as $land) {
                    if ($land->isLand($highVec3)) {
                        if (!LandService::checkLand($landStore, $land, $p->getXuid())) {
                            $popupMessage = "この土地は保護されています($land->name)";
                            continue 2;
                        }
                    } else {
                        if (in_array($p->getWorld()->getFolderName(), LandService::NEED_LAND_PROTECT)) {
                            $popupMessage = "このワールドは土地保護が必要です";
                            continue 2;
                        }
                    }
                }
                foreach ($highCheck as $bl) {
                    self::silentBreak($p->getWorld(), $bl, $hand, $p);
                }
                for ($nowY = $aabb->maxY; $nowY >= $aabb->minY; $nowY--) {
                    $bl = $p->getWorld()->getBlockAt($nowX, $nowY, $nowZ);
                    if ($bl->getBreakInfo()->getHardness() < 0 || $origin->equals($bl->getPosition())) continue;
                    if (!$isNoFreeze && $bl instanceof Liquid) {
                        $freezeBlock->position($p->getWorld(), $nowX, $nowY, $nowZ);
                        $bl = $freezeBlock;
                    }
                    $session->breakBlock();
                    $user->addXp($p, ServerUpdateTask::$exp_buff);
                    MoneyService::addMoney($repo, $p->getXuid(), 1);

                    self::silentBreak($p->getWorld(), $bl, $hand, $p);
                }
            }
        }
        if ($popupMessage !== "") $p->sendPopup($popupMessage);
        if (!SettingManager::isEnableOption($p->getXuid(), SettingConst::NO_FREEZE_WATER)) {
            self::freezeEdge($p, $aabb, $freezeBlock);
        }
    }

    private static function highCheck(Player $p, Vector3 $highVec): false|array
    {
        $flowable = [];

        $checkBl1 = $p->getWorld()->getBlock($highVec->add(0, 1, 0));
        if ($checkBl1 instanceof Air) return $flowable;
        if ($checkBl1 instanceof Flowable) $flowable[] = $checkBl1;
        $checkBl2 = $p->getWorld()->getBlock($highVec->add(0, 2, 0));
        if ($checkBl2 instanceof Air) return $flowable;
        if (!$checkBl2 instanceof Flowable) $flowable[] = $checkBl2;

        if (empty($flowable)) return false;
        return $flowable;
    }

    private static function getFreezeBlock(Item $hand): Block
    {
        $nbt = $hand->getNamedTag();
        $id = $nbt->getInt("frozen_block", 0);
        if ($id === 0) {
            return BlockFactory::getInstance()->get(BlockLegacyIds::STAINED_GLASS, 3);
        } else {
            return BlockFactory::getInstance()->get($id, 0);
        }
    }

    static function freezeEdge(Player $p, AxisAlignedBB $aabb, Block $freezeBl): void
    {
        $world = $p->getWorld();
        for ($nowY = $aabb->minY; $nowY <= $aabb->maxY; $nowY++) {
            for ($nowZ = $aabb->minZ; $nowZ <= $aabb->maxZ; $nowZ++) {
                $min = new Vector3($aabb->minX - 1, $nowY, $nowZ);
                $max = new Vector3($aabb->maxX + 1, $nowY, $nowZ);
                self::changeWater($world, $min, $freezeBl);
                self::changeWater($world, $max, $freezeBl);
            }
        }
        for ($nowX = $aabb->minX; $nowX <= $aabb->maxX; $nowX++) {
            for ($nowZ = $aabb->minZ; $nowZ <= $aabb->maxZ; $nowZ++) {
                $min = new Vector3($nowX, $aabb->minY - 1, $nowZ);
                $max = new Vector3($nowX, $aabb->maxY + 1, $nowZ);
                self::changeWater($world, $min, $freezeBl);
                self::changeWater($world, $max, $freezeBl);
            }
        }
        for ($nowX = $aabb->minX; $nowX <= $aabb->maxX; $nowX++) {
            for ($nowY = $aabb->minY; $nowY <= $aabb->maxY; $nowY++) {
                $min = new Vector3($nowX, $nowY, $aabb->minZ - 1);
                $max = new Vector3($nowX, $nowY, $aabb->maxZ + 1);
                self::changeWater($world, $min, $freezeBl);
                self::changeWater($world, $max, $freezeBl);
            }
        }
    }

    private static function changeWater(World $world, Vector3 $vec3, Block $replaceBlock): void // 水を水色のガラスに変える
    {
        $checkId = $world->getBlock($vec3)->getId();
        if (($checkId === BlockLegacyIds::WATER) || ($checkId === BlockLegacyIds::FLOWING_WATER)) { // 水を水色のガラスに変える
            $world->setBlock($vec3, $replaceBlock, false);
        }
    }

    /** @noinspection DuplicatedCode */
    static function silentBreak(World $world, Block $bl, Item $item = null, Player $p = null): void
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
            if ($bl instanceof Air || ($p->isSurvival() && !$bl->getBreakInfo()->isBreakable()) || $p->isSpectator()) {
                return;
            }

        } elseif (!$bl->getBreakInfo()->isBreakable()) {
            return;
        }

        foreach ($affectedBlocks as $t) {
//            $world->addParticle($t->getPosition()->add(0.5, 0.5, 0.5), new BlockBreakParticle($t));
            $tile = $world->getTile($t->getPosition());
            $tile?->onBlockDestroyed();

            $world->setBlock($t->getPosition(), VanillaBlocks::AIR(), false);
        }

        $item->onDestroyBlock($bl);

        foreach ($drops as $dropItem) {
            StackStorageAPI::$instance->add($p->getXuid(), $dropItem);
        }
        if ($xp > 0) {
            $p->getXpManager()->addXp($xp);
        }
    }
}
