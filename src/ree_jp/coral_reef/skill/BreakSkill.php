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

    const NORTH = 2;
    const SOUTH = 0;
    const EAST = 3;
    const WEST = 1;

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
        $direction = $p->getDirection();
        $isFly = AccountManager::hasValue($p->getXuid(), 'fly');
        $isDirectUnder = ($p->getFloorX() === $block->getFloorX()) && ($p->getFloorZ() === $block->getFloorZ());
        $widthSide = intval(floor($this->width / 2));
        $depthSide = intval(floor($this->depth / 2));
        if ($p->getFloorY() - 1 > $block->getFloorY() ||
            ((!$isFly || $isDirectUnder) && ($p->getFloorY() > $block->getFloorY()))) {

            $depthCeil = ceil($this->depth / 2);
            for ($height = 0; $height <= $this->height; ++$height) {
                for ($width = $widthSide; $width >= -$widthSide; --$width) {
                    for ($depth = $depthCeil; $depth >= -$depthSide; --$depth) {
                        if (!($height === 0 && $width === 0 && $depth === 0)) {
                            $vec = $this->getSideFromUserView($block->add(0, -$height), $direction, self::RIGHT, $width);
                            $vec = $this->getSideFromUserView($vec, $direction, self::FORWARD, $depth);
                            $this->breakBrockBySkill($p, $vec);
                        }
                    }
                }
            }
        } else {
            $isSkillHigh = ($block->getFloorY() - $p->getFloorY()) <= $this->height;
            $playerY = $p->getFloorY();
            for ($height = 0; $height <= $this->height; ++$height) {
                for ($width = $widthSide; $width >= -$widthSide; --$width) {
                    for ($depth = 0; $depth <= $this->depth; ++$depth) {
                        if ($isSkillHigh) {
                            $baseY = intval($height + $playerY);
                            if ($baseY === $block->getFloorY() && $width === 0 && $depth === 0) continue;
                            $base = new Vector3($block->x, $height + $playerY, $block->z);
                        } else {
                            if ($height === 0 && $width === 0 && $depth === 0) continue;// スキル起点のブロックへのスキル発動防止
                            $base = $block->add(0, $height);
                        }
                        $vec = $this->getSideFromUserView($base, $direction, self::RIGHT, $width);
                        $vec = $this->getSideFromUserView($vec, $direction, self::FORWARD, $depth);
                        $this->breakBrockBySkill($p, $vec);
                    }
                }
            }
        }
    }

    private function breakBrockBySkill(Player $p, Vector3 $vec): void
    {
        $hand = $p->getInventory()->getItemInHand();
        $level = $p->getLevel();
        $bl = $level->getBlock($vec);
        if ($bl->getHardness() < 0) return;

        $p->getLevel()->useBreakOn($vec, $hand, $p);
    }

    /**
     * @throws Exception
     */
    private function getSideFromUserView(Vector3 $vec3, int $view, int $direction, int $value): Vector3
    {
        switch ($view) {
            case self::NORTH:
                switch ($direction) {
                    case self::FORWARD:
                        return $vec3->add(-$value);
                    case self::BACKWARD:
                        return $vec3->add($value);
                    case self::RIGHT:
                        return $vec3->add(0, 0, -$value);
                    case self::LEFT:
                        return $vec3->add(0, 0, $value);
                    default:
                        throw new Exception('不正な方角');
                }
            case self::SOUTH:
                switch ($direction) {
                    case self::FORWARD:
                        return $vec3->add($value);
                    case self::BACKWARD:
                        return $vec3->add(-$value);
                    case self::RIGHT:
                        return $vec3->add(0, 0, -$value);
                    case self::LEFT:
                        return $vec3->add(0, 0, $value);
                    default:
                        throw new Exception('不正な方角');
                }
            case self::WEST:
                switch ($direction) {
                    case self::FORWARD:
                        return $vec3->add(0, 0, $value);
                    case self::BACKWARD:
                        return $vec3->add(0, 0, -$value);
                    case self::RIGHT:
                        return $vec3->add(-$value);
                    case self::LEFT:
                        return $vec3->add($value);
                    default:
                        throw new Exception('不正な方角');
                }
            case self::EAST:
                switch ($direction) {
                    case self::FORWARD:
                        return $vec3->add(0, 0, -$value);
                    case self::BACKWARD:
                        return $vec3->add(0, 0, $value);
                    case self::RIGHT:
                        return $vec3->add(-$value);
                    case self::LEFT:
                        return $vec3->add($value);
                    default:
                        throw new Exception('不正な方角');
                }
            default:
                throw new Exception('不正な視点の方角');
        }
    }
}
