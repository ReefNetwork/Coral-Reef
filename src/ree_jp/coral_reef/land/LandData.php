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
use pocketmine\world\Position;

class LandData
{
    public string $xuid;
    public string $name;
    public string $level;
    public AxisAlignedBB $aabb;

    /**
     * @var string[]
     */
    public array $members = [];

    public function __construct(string $xuid, string $name, string $level, AxisAlignedBB $aabb)
    {
        $this->xuid = $xuid;
        $this->name = $name;
        $this->level = $level;
        $this->aabb = $aabb;
    }

    public function isLand(Vector3 $pos): bool
    {
        if ($pos instanceof Position) {
            if ($pos->getWorld()->getFolderName() !== $this->level) return false;
        }
        return $this->aabb->isVectorInXZ($pos);
    }

    public function isMember(string $xuid): bool
    {
        return in_array($xuid, $this->members);
    }

    public function addMember(string $xuid): void
    {
        if (!$this->isMember($xuid)) {
            $this->members[] = $xuid;
        }
    }

    public function deleteMember(string $xuid): void
    {
        array_splice($this->members, $xuid);
    }
}
