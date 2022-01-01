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
use pocketmine\block\Flowable;
use pocketmine\math\Facing;
use pocketmine\math\Vector3;
use pocketmine\player\Player;

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
    public int $coolTime;
    public int $height; //縦 (掘ったブロックを含めず) ex: 5*5*5 A:4
    public int $width; //横 heightと同じ
    public int $depth; //奥行 heightと同じ
    public int $needLevel;
    public string $shortDetails;

    public function __construct(string $name, string $id, int $coolTime, int $height, int $width, int $depth, int $needLevel = 0, string $shortDetails = "")
    {
        $this->name = $name;
        $this->id = $id;
        $this->coolTime = $coolTime;
        $this->height = $height;
        $this->width = $width;
        $this->depth = $depth;
        $this->needLevel = $needLevel;
        $this->shortDetails = $shortDetails;
    }

    /**
     * @throws Exception
     */
    public function runSkill(Vector3 $block, Player $p): void
    {
        BreakService::frozeWater($p, $block, $p->getInventory()->getItemInHand());

        /** @var Block[] $needUpdate */
        /** @noinspection PhpArrayUsedOnlyForWriteInspection */
        $needUpdate = [];

        $world = $p->getWorld();
        $direction = $p->getHorizontalFacing();
        $widthSide = intval(floor($this->width / 2));
        $depthSide = intval(floor($this->depth / 2));
        if ($p->getPosition()->getFloorY() > $block->getFloorY()) {
            $depthCeil = ceil($this->depth / 2);

            for ($width = $widthSide; $width >= -$widthSide; --$width) {
                for ($depth = $depthCeil; $depth >= -$depthSide; --$depth) {

                    $checkVec = $this->getSideFromUserView($block->add(0, 2, 0), $direction, self::RIGHT, $width);
                    $checkVec = $this->getSideFromUserView($checkVec, $direction, self::FORWARD, $depth);
                    $checkBl = $p->getWorld()->getBlock($checkVec);
                    if (!$checkBl instanceof Flowable && !$checkBl instanceof Air) {
                        $checkBl2 = $checkBl->getSide(Facing::UP);
                        if (!$checkBl2 instanceof Flowable && !$checkBl2 instanceof Air) {
                            $p->sendPopup("上から掘ってください");
                            continue;
                        }
                    }

                    for ($height = 0; $height <= $this->height; ++$height) {
                        if (!($height === 0 && $width === 0 && $depth === 0)) {
                            $vec = $this->getSideFromUserView($block->add(0, -$height, 0), $direction, self::RIGHT, $width);
                            $vec = $this->getSideFromUserView($vec, $direction, self::FORWARD, $depth);
                            $bl = $world->getBlock($vec);
                            BreakService::breakBlockBySkill($p, $bl);
                            $needUpdate[] = $bl;
                        }
                    }
                }
            }
        } else {
            $isSkillHigh = ($block->getFloorY() - $p->getPosition()->getFloorY()) <= $this->height;
            $playerY = $p->getPosition()->getFloorY();

            for ($width = $widthSide; $width >= -$widthSide; --$width) {
                for ($depth = 0; $depth <= $this->depth; ++$depth) {

                    if ($isSkillHigh) {
                        $checkVec = new Vector3($block->x, $this->height + $playerY + 1, $block->z);
                    } else {
                        $checkVec = $block->add(0, $this->height + 1, 0);
                    }
                    $checkVec = $this->getSideFromUserView($checkVec, $direction, self::RIGHT, $width);
                    $checkVec = $this->getSideFromUserView($checkVec, $direction, self::FORWARD, $depth);
                    $checkBl = $p->getWorld()->getBlock($checkVec);
                    if (!$checkBl instanceof Flowable && !$checkBl instanceof Air) {
                        $checkBl2 = $checkBl->getSide(Facing::UP);
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
                            $base = $block->add(0, $height, 0);
                        }
                        $vec = $this->getSideFromUserView($base, $direction, self::RIGHT, $width);
                        $vec = $this->getSideFromUserView($vec, $direction, self::FORWARD, $depth);
                        $bl = $world->getBlock($vec);
                        BreakService::breakBlockBySkill($p, $bl);
                        $needUpdate[] = $bl;
                    }
                }
            }
        }
//        foreach ($needUpdate as $update) {
//            BreakService::updateBlock($p->getLevel(), $update);
//        }
    }

    /**
     * @throws Exception
     */
    private function getSideFromUserView(Vector3 $vec3, int $view, int $direction, int $value): Vector3
    {
        return match ($view) {
            self::NORTH => match ($direction) {
                self::FORWARD => $vec3->add(-$value, 0, 0),
                self::BACKWARD => $vec3->add($value, 0, 0),
                self::RIGHT => $vec3->add(0, 0, -$value),
                self::LEFT => $vec3->add(0, 0, $value),
                default => throw new Exception('不正な方角'),
            },
            self::SOUTH => match ($direction) {
                self::FORWARD => $vec3->add($value, 0, 0),
                self::BACKWARD => $vec3->add(-$value, 0, 0),
                self::RIGHT => $vec3->add(0, 0, -$value),
                self::LEFT => $vec3->add(0, 0, $value),
                default => throw new Exception('不正な方角'),
            },
            self::WEST => match ($direction) {
                self::FORWARD => $vec3->add(0, 0, $value),
                self::BACKWARD => $vec3->add(0, 0, -$value),
                self::RIGHT => $vec3->add(-$value, 0, 0),
                self::LEFT => $vec3->add($value, 0, 0),
                default => throw new Exception('不正な方角'),
            },
            self::EAST => match ($direction) {
                self::FORWARD => $vec3->add(0, 0, -$value),
                self::BACKWARD => $vec3->add(0, 0, $value),
                self::RIGHT => $vec3->add(-$value, 0, 0),
                self::LEFT => $vec3->add($value, 0, 0),
                default => throw new Exception('不正な方角'),
            },
            default => throw new Exception('不正な視点の方角'),
        };
    }
}
