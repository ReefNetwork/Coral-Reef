<?php
/*
 *  CCCCC                        lll RRRRRR                 fff
 * CC    C  oooo  rr rr    aa aa lll RR   RR   eee    eee  ff
 * CC      oo  oo rrr  r  aa aaa lll RRRRRR  ee   e ee   e ffff
 * CC    C oo  oo rr     aa  aaa lll RR  RR  eeeee  eeeee  ff
 *  CCCCC   oooo  rr      aaa aa lll RR   RR  eeeee  eeeee ff
 *
 * Copyright (c) 2021-2021. Ree-jp(https://ree-jp.net)
 */

namespace ree_jp\coral_reef\land;

use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\scheduler\ClosureTask;
use pocketmine\utils\TextFormat;
use pocketmine\world\particle\PortalParticle;
use pocketmine\world\Position;
use pocketmine\world\sound\EndermanTeleportSound;
use ree_jp\coral_reef\account\AccountService;
use ree_jp\coral_reef\account\AccountStore;
use ree_jp\coral_reef\CoralReefPlugin;
use ree_jp\coral_reef\sql\mysql\SQLRepository;
use ree_jp\coral_reef\sql\repo\LandRepository;
use ree_jp\coral_reef\sql\RepositoryPool;
use ree_jp\coral_reef\sql\SQLConst;
use ree_jp\coral_reef\StoreHouse;
use SOFe\AwaitGenerator\Await;

class LandService
{
    const CAN_CREATE_LAND = array("main_2");
    const LOBBY_WORLD = array("lobby", "shop");
    const NEED_LAND_PROTECT = array("main_2");

    static function getLand(LandStore $store, Position $pos): ?LandData
    {
        foreach ($store->lands as $level => $lands) {
            if ($level !== $pos->getWorld()->getFolderName()) continue;

            foreach ($lands as $land) {
                if ($land->isLand($pos)) return $land;
            }
        }
        return null;
    }

    /**
     * @return LandData[]
     */
    static function getMyLand(LandStore $store, string $xuid): array
    {
        $myLands = [];
        foreach ($store->lands as $lands) {
            foreach ($lands as $land) {
                if ($land->xuid === $xuid) {
                    $myLands[] = $land;
                }
            }
        }
        return $myLands;
    }

    static function canCreateLand(StoreHouse $store, LandData $checkLand): ?LandData
    {
        /** @var LandStore */
        $landStore = $store->get(LandStore::class);

        foreach ($landStore->lands as $level => $lands) {
            if ($level !== $checkLand->level) continue;

            foreach ($lands as $land) {
                if (self::isDuplicateWithoutY($land->aabb, $checkLand->aabb) && ($land->level === $level)) {
                    return $land;
                }
            }
        }
        return null;
    }

    /**
     * @param LandStore $store
     * @param string $level
     * @param AxisAlignedBB $aabb
     * @return LandData[]
     */
    static function getDuplicateLand(LandStore $store, string $level, AxisAlignedBB $aabb): array
    {
        $duplicateLands = [];
        foreach ($store->lands as $landLevel => $lands) {
            if ($landLevel !== $level) continue;

            foreach ($lands as $land) {

                if (self::isDuplicateWithoutY($aabb, $land->aabb) && ($land->level === $level)) {
                    $duplicateLands[] = $land;
                }
            }
        }
        return $duplicateLands;
    }

    static function isDuplicateWithoutY(AxisAlignedBB $aabb1, AxisAlignedBB $aabb2): bool
    {
        if ($aabb1->maxX - $aabb2->minX >= 0 and $aabb2->maxX - $aabb1->minX >= 0) {
            return $aabb1->maxZ - $aabb2->minZ >= 0 and $aabb2->maxZ - $aabb1->minZ >= 0;
        }

        return false;
    }

    static function addShareMember(RepositoryPool $pool, LandData $land, ?Player $p, string $xuid): void
    {
        /** @var SQLRepository */
        $sqlRepo = $pool->get(SQLRepository::class);

        if (!$land->isMember($xuid)) {
            $land->addMember($xuid);
            $sqlRepo->setValue($land->xuid, SQLConst::TYPE_LAND_KEY, CoralReefPlugin::$serverID . ":" . $land->name, implode(":", $land->members),
                function () use ($p): void {
                    if (!is_null($p)) $p->sendMessage("土地保護の共有を追加しました");
                }
            );
        }
    }

    static function deleteShareMember(RepositoryPool $pool, LandData $land, ?Player $p, string $xuid): void
    {
        /** @var SQLRepository */
        $sqlRepo = $pool->get(SQLRepository::class);

        $land->deleteMember($xuid);
        $sqlRepo->setValue($land->xuid, SQLConst::TYPE_LAND_KEY, CoralReefPlugin::$serverID . ":" . $land->name, implode(":", $land->members),
            function () use ($p): void {
                if (!is_null($p)) $p->sendMessage("土地保護の共有を削除しました");
            }
        );
    }

    static function addLand(RepositoryPool $pool, StoreHouse $store, LandData $land, ?Player $p): void
    {
        $p->sendMessage("一時的に機能制限をしています");
        return;
        /** @var LandRepository */
        $repo = $pool->get(LandRepository::class);
        /** @var LandStore */
        $landStore = $store->get(LandStore::class);

        Await::g2c($repo->setLand($land, CoralReefPlugin::$serverID), function () use ($landStore, $p, $land) {
            if (!isset($store->lands[$land->level])) $landStore->lands[$land->level] = [];
            $landStore->lands[$land->level][] = $land;

            if ($p instanceof Player && $p->isOnline()) {
                $p->sendMessage($land->name . 'を作成しました');
            }
        });
    }

    static function deleteLand(RepositoryPool $pool, StoreHouse $store, LandData $land, ?Player $p): void
    {
        $p->sendMessage("一時的に機能制限をしています");
        return;
        /** @var LandRepository */
        $repo = $pool->get(LandRepository::class);
        /** @var LandStore */
        $landStore = $store->get(LandStore::class);

        Await::g2c($repo->deleteLand($land));

        foreach ($landStore->lands as $level => $cacheLands) {
            if ($level !== $land->level) continue;

            foreach ($cacheLands as $key => $cacheLand) {
                if ($cacheLand->xuid === $land->xuid && $cacheLand->name === $land->name) {
                    array_splice($landStore->lands[$level], $key, 1);
                    $p->sendMessage("土地を削除しました");
                    return;
                }
            }
        }
        $p->sendMessage("エラーが発生しました");
    }

    static function protect(LandStore $landStore, AccountStore $accountStore, Player $p, Position $pos, ?string $message,
                            bool      $isTouch = false, bool $isSlidingBrock = false): bool
    {
        if (in_array($p->getWorld()->getFolderName(), self::LOBBY_WORLD) && !(AccountService::isOp($p) && $p->isCreative())) {
            if (is_null($message)) return false;
            $p->sendPopup($message);
        } else {
            $land = self::getLand($landStore, $pos);
            if (is_null($land)) {
                if (in_array($p->getWorld()->getFolderName(), self::NEED_LAND_PROTECT)) { // 土地保護しないと掘れないワールド
                    if ($isTouch) return false;
                    $p->sendPopup("このワールドは土地保護が必要です");
                    if (AccountService::isOp($p) && $p->isCreative()) return false;
                } else {
                    return false;
                }
            } else {
                if (self::checkLand($landStore, $land, $p->getXuid())) return false;
                $name = $accountStore->getUserName($land->xuid);
                $p->sendPopup("この土地は$name によって保護されています($land->name)");
                if (AccountService::isOp($p) && $p->isCreative()) return false;
            }
        }
        if ($isSlidingBrock && !$accountStore->hasValue($p->getXuid(), "sliding_brock")) {
            $accountStore->setValue($p->getXuid(), "sliding_brock", 5);

            $originPos = $p->getPosition();
            CoralReefPlugin::$plugin->getScheduler()->scheduleDelayedTask(new ClosureTask(function () use ($originPos, $p): void {
                if ($p->isOnline()) {
                    $p->teleport($originPos);
                }
            }), 5);
        }
        if (!$accountStore->hasValue($p->getXuid(), 'protect_warning')) {
            $accountStore->setValue($p->getXuid(), 'protect_warning', 10);
            $p->getWorld()->addSound($p->getPosition(), new EndermanTeleportSound(), [$p]);
            $particleVec = $pos->add(0.5, 1.5, 0.5);
            for ($count = 0; $count < 30; $count++) {
                $p->getWorld()->addParticle($particleVec->add(mt_rand(-10, 10) * 0.1, 0, mt_rand(-10, 10) * 0.1), new PortalParticle(), [$p]);
            }
        }
        return true;
    }

    // その人が掘れる土地だったらtrue
    static function checkLand(LandStore $store, LandData $land, string $xuid): bool
    {
        return $land->xuid === $xuid || $land->isMember($xuid) || $store->isParty($land->xuid, $xuid);
    }

    static function checkSpace(LandStore $store, Player $p): void
    {
        $xuid = $p->getXuid();
        if (isset($store->pos[$xuid][1]) && isset($store->pos[$xuid][2])) {
            $vec1 = $store->pos[$xuid][1];
            $vec2 = $store->pos[$xuid][2];
            if ($vec1 instanceof Vector3 && $vec2 instanceof Vector3) {
                $aabb = self::getAabb($vec1->getFloorX(), 0, $vec1->getFloorZ(), $vec2->getFloorX(), 0, $vec2->getFloorZ());
                $aabb->minY = $p->getPosition()->getFloorY();
                $aabb->maxY = $p->getPosition()->getFloorY() + 3;
                $p->sendMessage(TextFormat::DARK_GRAY . "指定されている範囲を表示しています");
                for ($x = $aabb->minX; $x <= $aabb->maxX; $x += 0.6) {
                    self::sendCheckSpaceEffect($p, $aabb, $x, $aabb->minZ);
                    self::sendCheckSpaceEffect($p, $aabb, $x, $aabb->maxZ);
                }
                for ($z = $aabb->minZ; $z <= $aabb->maxZ; $z += 0.6) {
                    self::sendCheckSpaceEffect($p, $aabb, $aabb->minX, $z);
                    self::sendCheckSpaceEffect($p, $aabb, $aabb->maxX, $z);
                }
            } else $p->sendMessage('エラーが発生しました');
        } else $p->sendMessage('地点を2つとも設定してください');
    }

    static function sendCheckSpaceEffect(Player $p, AxisAlignedBB $aabb, int $x, int $z): void
    {
        for ($y = $aabb->minY; $y <= $aabb->maxY; $y += 0.6) {
            $p->getWorld()->addParticle(new Vector3($x + 0.5, $y, $z + 0.5), new PortalParticle(), [$p]);
            $p->getWorld()->addParticle(new Vector3($x + 0.5, $y, $z + 0.5), new PortalParticle(), [$p]);
        }
    }

    static function getAabb(int $x1, int $y1, int $z1, int $x2, int $y2, int $z2): AxisAlignedBB
    {
        if ($x1 > $x2) {
            $minX = $x2;
            $maxX = $x1;
        } else {
            $minX = $x1;
            $maxX = $x2;
        }
        if ($y1 > $y2) {
            $minY = $y2;
            $maxY = $y1;
        } else {
            $minY = $y1;
            $maxY = $y2;
        }
        if ($z1 > $z2) {
            $minZ = $z2;
            $maxZ = $z1;
        } else {
            $minZ = $z1;
            $maxZ = $z2;
        }
        return new AxisAlignedBB($minX, $minY, $minZ, $maxX, $maxY, $maxZ);
    }
}
