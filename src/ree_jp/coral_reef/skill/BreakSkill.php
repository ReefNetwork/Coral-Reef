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
use pocketmine\block\Air;
use pocketmine\block\Block;
use pocketmine\block\BlockIds;
use pocketmine\block\Flowable;
use pocketmine\block\Liquid;
use pocketmine\level\Level;
use pocketmine\math\Vector3;
use pocketmine\Player;
use ree_jp\coral_reef\account\SettingManager;
use ree_jp\coral_reef\sql\SettingConst;

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
    public int $needLevel;

    public function __construct(string $name, string $id, int $cool_time, int $height, int $width, int $depth, int $needLevel = 0)
    {
        $this->name = $name;
        $this->id = $id;
        $this->cool_time = $cool_time;
        $this->height = $height;
        $this->width = $width;
        $this->depth = $depth;
        $this->needLevel = $needLevel;
    }

    /**
     * @throws Exception
     */
    public function runSkill(Vector3 $block, Player $p): void
    {
        $this->frozeWater($p, $block);

        $direction = $p->getDirection();
        $widthSide = intval(floor($this->width / 2));
        $depthSide = intval(floor($this->depth / 2));
        if ($p->getFloorY() > $block->getFloorY()) {
            $depthCeil = ceil($this->depth / 2);

            for ($width = $widthSide; $width >= -$widthSide; --$width) {
                for ($depth = $depthCeil; $depth >= -$depthSide; --$depth) {

                    $checkVec = $this->getSideFromUserView($block->add(0, 1), $direction, self::RIGHT, $width);
                    $checkVec = $this->getSideFromUserView($checkVec, $direction, self::FORWARD, $depth);
                    $checkBl = $p->getLevel()->getBlock($checkVec);
                    if (!$checkBl instanceof Flowable && !$checkBl instanceof Air) {
                        $checkBl2 = $checkBl->add(0, 1);
                        if (!$checkBl2 instanceof Flowable && !$checkBl2 instanceof Air) {
                            $p->sendPopup("上から掘ってください");
                            continue;
                        }
                    }

                    for ($height = 0; $height <= $this->height; ++$height) {
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

            for ($width = $widthSide; $width >= -$widthSide; --$width) {
                for ($depth = 0; $depth <= $this->depth; ++$depth) {

                    if ($isSkillHigh) {
                        $checkVec = new Vector3($block->x, $this->height + $playerY + 1, $block->z);
                    } else {
                        $checkVec = $block->add(0, $this->height + 1);
                    }
                    $checkVec = $this->getSideFromUserView($checkVec, $direction, self::RIGHT, $width);
                    $checkVec = $this->getSideFromUserView($checkVec, $direction, self::FORWARD, $depth);
                    $checkBl = $p->getLevel()->getBlock($checkVec);
                    if (!$checkBl instanceof Flowable && !$checkBl instanceof Air) {
                        $checkBl2 = $checkBl->add(0, 1);
                        if (!$checkBl2 instanceof Flowable && !$checkBl2 instanceof Air) {
                            $p->sendPopup("上から掘ってください");
                            continue;
                        }
                    }
                    for ($height = 0; $height <= $this->height; ++$height) {
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
        $this->frozeWater($p, $vec);

        $bl = $p->getLevel()->getBlock($vec);
        $hand = $p->getInventory()->getItemInHand();
        if ($bl->getHardness() < 0 || $bl instanceof Liquid) return;

        $p->getLevel()->useBreakOn($vec, $hand, $p);
    }

    private function frozeWater(Player $p, Vector3 $vec): void
    {
        if (!SettingManager::isEnableOption($p->getXuid(), SettingConst::NO_FREEZE_WATER)) {
            try {
                $this->changeWater($p->getLevel(), $vec);
                $this->changeWater($p->getLevel(), $vec->add(0, 1));
                $this->changeWater($p->getLevel(), $vec->add(0, -1));
                $this->changeWater($p->getLevel(), $vec->add(1));
                $this->changeWater($p->getLevel(), $vec->add(-1));
                $this->changeWater($p->getLevel(), $vec->add(0, 0, 1));
                $this->changeWater($p->getLevel(), $vec->add(0, 0, -1));
            } catch (Exception $ex) {
                $p->sendMessage("エラーが発生しました");
            }
        }
    }

    private function changeWater(?Level $level, Vector3 $vec3): void // 水を水色のガラスに変える
    {
        if (is_null($level)) return;
        if ($level->getBlock($vec3)->getId() === BlockIds::WATER) { // 水を水色のガラスに変える
            $level->setBlock($vec3, Block::get(BlockIds::STAINED_GLASS, 3));
        }
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
