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
use pocketmine\math\Facing;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use ree_jp\coral_reef\account\UserAccount;
use ree_jp\coral_reef\land\LandService;
use ree_jp\coral_reef\land\LandStore;
use ree_jp\coral_reef\session\SessionData;
use ree_jp\coral_reef\sql\mysql\SQLRepository;

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
     * スキルの範囲をAABBにして求める
     */
    public function runSkill(SQLRepository $repo, LandStore $landStore, Vector3 $blockVec, Player $p, SessionData $session, UserAccount $user): void
    {
        $direction = $p->getHorizontalFacing();
        $widthSide = intval(floor($this->width / 2));
        $depthSide = intval(floor($this->depth / 2));
        $playerY = $this->exactFloorY($p);

        $right = $this->getSideFromUserView(Vector3::zero(), $direction, self::RIGHT, $widthSide);
        if ($playerY > $blockVec->getFloorY()) {
            // 下のブロックを掘ったとき
            $forward = $this->getSideFromUserView(Vector3::zero(), $direction, self::FORWARD, $depthSide);
            $rightForward = $p->getPosition()->addVector($right)->addVector($forward);
            $leftBackward = $p->getPosition()->subtractVector($right)->subtractVector($forward);

            $highY = $playerY - 1;
            $lowY = $highY - $this->height;
        } else {
            // ブロックがプレイヤーより上の場合

            // プレイヤーの足を一番下としてスキルの範囲の高さにはいっていた場合スキルを発動する範囲がその範囲に自動調整される
            // 掘られた場所が範囲より高かったらその場所を一番下にして範囲を計算する
            $isSkillHigh = ($blockVec->getFloorY() - $playerY) <= $this->height;

            $forward = $this->getSideFromUserView(Vector3::zero(), $direction, self::FORWARD, $this->depth);
            $rightForward = $p->getPosition()->addVector($right)->addVector($forward);
            $leftBackward = $p->getPosition()->subtractVector($right);

            if ($isSkillHigh) {
                $highY = $playerY + $this->height;
                $lowY = $playerY;
            } else {
                $highY = $blockVec->getFloorY() + $this->height;
                $lowY = $blockVec->getFloorY();
            }
        }
        $aabb = LandService::getAabb($rightForward->getX(), $lowY, $rightForward->getZ(), $leftBackward->getX(), $highY, $leftBackward->getZ());
        BreakService::breakBlockBySkill($repo, $landStore, $session, $p, $user, $aabb, $blockVec);
    }

    private function exactFloorY(Player $p): int
    {
        $stupidY = $p->getPosition()->getY();
        return round($stupidY, 1);
    }

    /**
     * @throws Exception
     */
    private function getSideFromUserView(Vector3 $vec3, int $viewDirection, int $target, int $value): Vector3
    {
        return match ($viewDirection) {
            Facing::NORTH => match ($target) {
                self::FORWARD => $vec3->add(0, 0, -$value),
                self::BACKWARD => $vec3->add(0, 0, $value),
                self::RIGHT => $vec3->add(-$value, 0, 0),
                self::LEFT => $vec3->add($value, 0, 0),
                default => throw new Exception('不正な方角'),
            },
            Facing::SOUTH => match ($target) {
                self::FORWARD => $vec3->add(0, 0, $value),
                self::BACKWARD => $vec3->add(0, 0, -$value),
                self::RIGHT => $vec3->add(-$value, 0, 0),
                self::LEFT => $vec3->add($value, 0, 0),
                default => throw new Exception('不正な方角'),
            },
            Facing::WEST => match ($target) {
                self::FORWARD => $vec3->add(-$value, 0, 0),
                self::BACKWARD => $vec3->add($value, 0, 0),
                self::RIGHT => $vec3->add(0, 0, -$value),
                self::LEFT => $vec3->add(0, 0, $value),

                default => throw new Exception('不正な方角'),
            },
            Facing::EAST => match ($target) {
                self::FORWARD => $vec3->add($value, 0, 0),
                self::BACKWARD => $vec3->add(-$value, 0, 0),
                self::RIGHT => $vec3->add(0, 0, -$value),
                self::LEFT => $vec3->add(0, 0, $value),
                default => throw new Exception('不正な方角'),
            },
            default => throw new Exception('不正な視点の方角'),
        };
    }
}
