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

use pocketmine\level\Position;
use pocketmine\math\AxisAlignedBB;

class LandData
{
    public string $xuid;
    public string $name;
    public string $level;
    public AxisAlignedBB $aabb;

    public function __construct(string $xuid, string $name, string $level, AxisAlignedBB $aabb)
    {
        $this->xuid = $xuid;
        $this->name = $name;
        $this->level = $level;
        $this->aabb = $aabb;
    }

    public function isLand(Position $pos): bool
    {
        if ($pos->getLevelNonNull()->getFolderName() === $this->level) {
            return $this->aabb->isVectorInXZ($pos);
        }
        return false;
    }
}
