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

namespace ree_jp\coral_reef\skill;

use Exception;
use pocketmine\math\Vector3;
use pocketmine\Player;
use ree_jp\coral_reef\account\AccountManager;

class BreakSkill
{
    const FORWARD = 1;
    const BACKWARD = 2;
    const RIGHT = 3;
    const LEFT = 4;

    public string $name;
    public string $id;
    public int $cool_time;
    public int $height; //縦 (掘ったブロックを含めず) ex: 5*5*5 A:4
    public int $width; //横 heightと同じ
    public int $depth; //奥行 heightと同じ

    public function __construct(string $name, string $id, int $cool_time, int $height, int $width, int $depth)
    {
        $this->name = $name;
        $this->id = $id;
        $this->cool_time = $cool_time;
        $this->height = $height;
        $this->width = $width;
        $this->depth = $depth;
    }

    /**
     * @throws Exception
     */
    public function runSkill(Vector3 $block, Player $p): void
    {
        $isFly = AccountManager::hasValue($p->getXuid(), 'fly');
        $isDirectUnder = ($p->getFloorX() === $block->getFloorX()) && ($p->getFloorZ() === $block->getFloorZ());
        $widthSide = $this->width / 2;
        if ($p->getFloorY() - 1 > $block->getFloorY() ||
            ((!$isFly || $isDirectUnder) && ($p->getFloorY() > $block->getFloorY()))) {

        } elseif ($block->getFloorY() - $p->getFloorY() < $this->height) {

        } else {

        }
    }

    /**
     * @throws Exception
     */
    private function getSideFromUserView(Vector3 $vec3, int $view, int $direction): Vector3
    {
        switch ($view) {
            case Vector3::SIDE_NORTH:
                switch ($direction) {
                    case self::FORWARD:
                        return $vec3->add(0, 0, 1);
                    case self::BACKWARD:
                        return $vec3->add(0, 0, -1);
                    case self::RIGHT:
                        return $vec3->add(1);
                    case self::LEFT:
                        return $vec3->add(-1);
                    default:
                        throw new Exception('不正な方角');
                }
            case Vector3::SIDE_SOUTH:
                switch ($direction) {
                    case self::FORWARD:
                        return $vec3->add(0, 0, -1);
                    case self::BACKWARD:
                        return $vec3->add(0, 0, 1);
                    case self::RIGHT:
                        return $vec3->add(-1);
                    case self::LEFT:
                        return $vec3->add(1);
                    default:
                        throw new Exception('不正な方角');
                }
            case Vector3::SIDE_WEST:
                switch ($direction) {
                    case self::FORWARD:
                        return $vec3->add(-1);
                    case self::BACKWARD:
                        return $vec3->add(1);
                    case self::RIGHT:
                        return $vec3->add(0, 0, -1);
                    case self::LEFT:
                        return $vec3->add(0, 0, 1);
                    default:
                        throw new Exception('不正な方角');
                }
            case Vector3::SIDE_EAST:
                switch ($direction) {
                    case self::FORWARD:
                        return $vec3->add(1);
                    case self::BACKWARD:
                        return $vec3->add(-1);
                    case self::RIGHT:
                        return $vec3->add(0, 0, 1);
                    case self::LEFT:
                        return $vec3->add(0, 0, -1);
                    default:
                        throw new Exception('不正な方角');
                }
            default:
                throw new Exception('不正な視点の方角');
        }
    }
}
