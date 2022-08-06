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

    /**
     * @param string $server
     * @return Generator LandData[]
     */
    public function getLands(string $server): Generator
    {
        $result = yield from Await::promise(
            fn($resolve, $reject) => $this->pool->getConnection()->executeSelect("coral_reef.land.get",
                ["server" => $server], $resolve, $reject));
        if (!$result) return [];

        return $this->setLandModels($result);
    }

    private function setLandModels(array $data): array
    {
        $lands = [];
        foreach ($data as $landRaw) {
            $lands[] = new LandData(strval($landRaw["xuid"]), $landRaw["name"], $landRaw["level"], new AxisAlignedBB($landRaw["sx"], 0, $landRaw["sz"],
                $landRaw["mx"], 0, $landRaw["mz"]));
        }
        return $lands;
    }

    public function setLand(LandData $land, string $server): Generator
    {
        yield from Await::promise(
            fn($resolve, $reject) => $this->pool->getConnection()->executeInsert("coral_reef.land.create", ["xuid" => intval($land->xuid),
                "name" => $land->name, "server" => $server, "level" => $land->level,
                "mx" => $land->aabb->maxX, "sx" => $land->aabb->minX, "mz" => $land->aabb->maxZ, "sz" => $land->aabb->minZ], $resolve, $reject));
    }

    public function deleteLand(LandData $land): Generator
    {
        yield from Await::promise(
            fn($resolve, $reject) => $this->pool->getConnection()->executeInsert("coral_reef.land.delete", ["xuid" => intval($land->xuid),
                "name" => $land->name], $resolve, $reject));
    }

    public function isExistLand(string $xuid, string $name): Generator
    {
        $result = yield from Await::promise(
            fn($resolve, $reject) => $this->pool->getConnection()->executeSelect("coral_reef.land.get_once",
                ["xuid" => intval($xuid), "name" => $name], $resolve, $reject));
        if (!$result) return null;

        return current($this->setLandModels($result));
    }

    public function close(): void
    {
    }
}