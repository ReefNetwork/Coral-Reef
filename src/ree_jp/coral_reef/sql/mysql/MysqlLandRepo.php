<?php
/*
 *  CCCCC                        lll RRRRRR                 fff
 * CC    C  oooo  rr rr    aa aa lll RR   RR   eee    eee  ff
 * CC      oo  oo rrr  r  aa aaa lll RRRRRR  ee   e ee   e ffff
 * CC    C oo  oo rr     aa  aaa lll RR  RR  eeeee  eeeee  ff
 *  CCCCC   oooo  rr      aaa aa lll RR   RR  eeeee  eeeee ff
 *
 * Copyright (c) 2022. Ree-jp(https://ree-jp.net)
 */

namespace ree_jp\coral_reef\sql\mysql;

use Generator;
use pocketmine\math\AxisAlignedBB;
use ree_jp\coral_reef\land\LandData;
use ree_jp\coral_reef\sql\repo\LandRepository;
use ree_jp\coral_reef\sql\RepositoryPool;
use SOFe\AwaitGenerator\Await;

class MysqlLandRepo implements LandRepository
{
    public function __construct(private RepositoryPool $pool, bool $isInit)
    {
        if ($isInit) {
            $pool->getConnection()->executeGeneric("coral_reef.init.tables.land");
        }
    }

    public function getLands(string $server): Generator
    {
        $result = yield from Await::promise(
            fn($resolve, $reject) => $this->pool->getConnection()->executeInsert("coral_reef.land.get",
                ["server" => $server], $resolve, $reject));
        $lands = [];
        foreach ($result as $data) {
            $lands[] = $this->setLandModel($data);
        }
        return $lands;
    }

    private function setLandModel(array $data): LandData
    {
        return new LandData($data["xuid"], $data["name"], $data["level"], new AxisAlignedBB($data["sx"], 0, $data["sz"], $data["mx"], 0, $data["mz"]));
    }

    public function setLand(LandData $land, string $server): Generator
    {
        yield from Await::promise(
            fn($resolve, $reject) => $this->pool->getConnection()->executeInsert("coral_reef.land.create", ["xuid" => $land->xuid,
                "name" => $land->name, "server" => $server, "level" => $land->level,
                "mx" => $land->aabb->maxX, "sx" => $land->aabb->minX, "mz" => $land->aabb->maxZ, "sz" => $land->aabb->minZ], $resolve, $reject));
    }

    public function deleteLand(LandData $land): Generator
    {
        yield from Await::promise(
            fn($resolve, $reject) => $this->pool->getConnection()->executeInsert("coral_reef.land.delete", ["xuid" => $land->xuid,
                "name" => $land->name], $resolve, $reject));
    }

    public function close(): void
    {
    }
}