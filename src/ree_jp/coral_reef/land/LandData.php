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
use ree_jp\coral_reef\CoralReefPlugin;

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
            CoralReefPlugin::$plugin->getLogger()->debug("土地保護共有追加しました[$this->name]($xuid)");
        } else {
            CoralReefPlugin::$plugin->getLogger()->error("土地保護共有追加に失敗しました[$this->name]($xuid)");
        }
        var_dump($this->members);
    }

    public function deleteMember(string $xuid): void
    {
        $this->members = array_diff($this->members, [$xuid]);
        $this->members = array_values($this->members);
    }
}
