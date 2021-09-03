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

use Exception;
use pocketmine\block\Block;
use pocketmine\level\particle\PortalParticle;
use pocketmine\level\Position;
use pocketmine\level\sound\GenericSound;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\LevelEventPacket;
use pocketmine\Player;
use ree_jp\coral_reef\account\AccountManager;
use ree_jp\coral_reef\form\PartyForm;
use ree_jp\coral_reef\sql\SQLManager;

class LandManager
{
    static LandManager $instance;
    static array $pos;

    /**
     * @var LandData[]
     */
    private array $lands = [];

    /**
     * @throws Exception
     */
    public function __construct()
    {
        if (is_null(SQLManager::$manager)) throw new Exception('データベースにアクセス出来ませんでした');
        $arrayLands = SQLManager::$manager->getAllProtectLand();
        foreach ($arrayLands as $arrayLand) {
            if (!(isset($arrayLand['XUID']) && isset($arrayLand['NAME']) && isset($arrayLand['LEVEL']) && isset($arrayLand['MX']) && isset($arrayLand['SX']) &&
                isset($arrayLand['MZ']) && isset($arrayLand['SZ']))) throw new Exception('土地の情報が不足しています');
            array_push($this->lands, new LandData($arrayLand['XUID'], $arrayLand['NAME'], $arrayLand['LEVEL'],
                new AxisAlignedBB($arrayLand['SX'], 0, $arrayLand['SZ'], $arrayLand['MX'], 0, $arrayLand['MZ'])));
        }
    }

    public function getLand(Position $pos): ?LandData
    {
        foreach ($this->lands as $land) {
            if ($land->isLand($pos)) {
                return $land;
            }
        }
        return null;
    }

    public function getMyLand(string $xuid): array
    {
        $myLands = [];
        foreach ($this->lands as $land) {
            if ($land->xuid === $xuid) {
                array_push($myLands, $land);
            }
        }
        return $myLands;
    }

    public function canCreateLand(AxisAlignedBB $aabb): ?LandData
    {
        foreach ($this->lands as $land) {
            if ($land->aabb->intersectsWith($aabb, -0.00001)) {
                return $land;
            }
        }
        return null;
    }

    public function protect(Player $p, Block $bl, ?string $message): bool
    {
        if (in_array($p->getLevel()->getFolderName(), ['lobby', 'lobby2']) && !($p->isOp() && $p->isCreative())) {
            if (is_null($message)) return false;
            $p->sendTip($message);
        } else {
            $land = self::$instance->getLand($bl);
            if (is_null($land) || $land->xuid === $p->getXuid() || PartyForm::isParty($land->xuid, $p->getXuid())) return false;

            $name = AccountManager::getUserName($land->xuid);
            $p->sendTip("この土地は$name によって保護されています($land->name)");
            if ($p->isOp() && $p->isCreative()) return false;
        }
        if (!AccountManager::hasValue($p->getXuid(), 'protect_warning')) {
            AccountManager::setValue($p->getXuid(), 'protect_warning', 10);
            $p->getLevelNonNull()->addSound(new GenericSound($bl, LevelEventPacket::EVENT_SOUND_PORTAL), [$p]);
            $particleVec = $bl->add(0.5, 1.5, 0.5);
            for ($count = 0; $count < 30; $count++) {
                $p->getLevelNonNull()->addParticle(new PortalParticle(
                    $particleVec->add(mt_rand(-10, 10) * 0.1, 0, mt_rand(-10, 10) * 0.1)), [$p]);
            }
        }
        return true;
    }

    public function checkSpace(Player $p): void
    {
        $xuid = $p->getXuid();
        if (isset(LandManager::$pos[$xuid][1]) && isset(LandManager::$pos[$xuid][2])) {
            $vec1 = LandManager::$pos[$xuid][1];
            $vec2 = LandManager::$pos[$xuid][2];
            if ($vec1 instanceof Vector3 && $vec2 instanceof Vector3) {
                $aabb = $this->getAabb($vec1->getFloorX(), $vec1->getFloorZ(), $vec2->getFloorX(), $vec2->getFloorZ());
                $aabb->minY = $p->getFloorY();
                $aabb->maxY = $p->getFloorY() + 3;
                $p->sendMessage("指定されている範囲を表示しています");
                for ($x = $aabb->minX; $x <= $aabb->maxX; $x++) {
                    $this->sendCheckSpaceEffect($p, $aabb, $x, $aabb->minZ);
                    $this->sendCheckSpaceEffect($p, $aabb, $x, $aabb->maxZ);
                }
                for ($z = $aabb->minZ; $z <= $aabb->maxZ; $z++) {
                    $this->sendCheckSpaceEffect($p, $aabb, $aabb->minX, $z);
                    $this->sendCheckSpaceEffect($p, $aabb, $aabb->maxX, $z);
                }
            } else $p->sendMessage('エラーが発生しました');
        } else $p->sendMessage('地点を2つとも設定してください');
    }

    private function sendCheckSpaceEffect(Player $p, AxisAlignedBB $aabb, int $x, int $z): void
    {
        for ($y = $aabb->minY; $y <= $aabb->maxY; $y += 0.3) {
            $p->getLevelNonNull()->addParticle(new PortalParticle(
                new Vector3($x + 0.5, $y, $z + 0.5)), [$p]);
            $p->getLevelNonNull()->addParticle(new PortalParticle(
                new Vector3($x + 0.5, $y, $z + 0.5)), [$p]);
        }
    }

    public function getAabb(int $x1, int $z1, int $x2, int $z2): AxisAlignedBB
    {
        if ($x1 > $x2) {
            $minX = $x2;
            $maxX = $x1;
        } else {
            $minX = $x1;
            $maxX = $x2;
        }
        if ($z1 > $z2) {
            $minZ = $z2;
            $maxZ = $z1;
        } else {
            $minZ = $z1;
            $maxZ = $z2;
        }
        return new AxisAlignedBB($minX, 0, $minZ, $maxX, 0, $maxZ);
    }
}
