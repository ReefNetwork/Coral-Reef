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
use pocketmine\Server;
use pocketmine\world\Position;
use ree_jp\coral_reef\sql\model\WarpPoint;
use ree_jp\coral_reef\sql\repo\WarpRepository;
use ree_jp\coral_reef\sql\RepositoryPool;
use SOFe\AwaitGenerator\Await;

class MysqlWarpRepo implements WarpRepository
{
    public function __construct(private RepositoryPool $pool, bool $isInit)
    {
        if ($isInit) {
            $pool->getConnection()->executeGeneric("coral_reef.init.tables.warp");
        }
    }

    /**
     * @param string $xuid
     * @param string $server
     * @return Generator WarpPoint[]
     */
    public function getWarps(string $xuid, string $server): Generator
    {
        $result = yield from Await::promise(
            fn($resolve, $reject) => $this->pool->getConnection()->executeSelect("coral_reef.warp.get", ["xuid" => intval($xuid), "server" => $server],
                $resolve, $reject));
        if (!$result) return [];

        return $this->setWarpPointModels($result);
    }

    public function setWarp(WarpPoint $warp): Generator
    {
        $pos = $warp->pos;
        yield from Await::promise(
            fn($resolve, $reject) => $this->pool->getConnection()->executeInsert("coral_reef.warp.create", ["xuid" => intval($warp->xuid),
                "name" => $warp->warpName, "server" => $warp->server, "level" => $pos->getWorld()->getFolderName(),
                "x" => $pos->getFloorX(), "y" => $pos->getFloorY(), "z" => $pos->getFloorZ()], $resolve, $reject));
    }

    public function deleteWarp(WarpPoint $warp): Generator
    {
        yield from Await::promise(
            fn($resolve, $reject) => $this->pool->getConnection()->executeInsert("coral_reef.warp.delete", ["xuid" => intval($warp->xuid),
                "name" => $warp->warpName, "server" => $warp->server], $resolve, $reject));
    }

    public function isExistWarp(string $xuid, string $name): Generator
    {
        $result = yield from Await::promise(
            fn($resolve, $reject) => $this->pool->getConnection()->executeSelect("coral_reef.warp.get_once",
                ["xuid" => intval($xuid), "name" => $name], $resolve, $reject));
        if (!$result) return null;

        return current($this->setWarpPointModels($result));
    }

    private function setWarpPointModels(array $data): array
    {
        $warps = [];
        foreach ($data as $warpRaw) {
            $warps[] = new WarpPoint(strval($warpRaw["xuid"]), $warpRaw["name"], $warpRaw["server"], new Position($warpRaw["x"], $warpRaw["y"], $warpRaw["z"],
                Server::getInstance()->getWorldManager()->getWorldByName($warpRaw["level"])));
        }
        return $warps;
    }

    public function close(): void
    {
    }
}