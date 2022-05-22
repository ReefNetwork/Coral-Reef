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
use ree_jp\coral_reef\sql\model\PlayerData;
use ree_jp\coral_reef\sql\PlayerRepository;
use ree_jp\coral_reef\sql\RepositoryPool;
use SOFe\AwaitGenerator\Await;

class MysqlPlayerDataRepo implements PlayerRepository
{
    public function __construct(private RepositoryPool $pool, bool $isInit)
    {
        if ($isInit) {
            $pool->getConnection()->executeGeneric("coral_reef.init.tables.player_data");
        }
    }

    public function getPlayerData(string $xuid): Generator
    {
        [$result] = yield from Await::promise(
            fn($resolve, $reject) => $this->pool->getConnection()->executeSelect("coral_reef.player_data.get", ["xuid" => $xuid], $resolve, $reject));
        return $this->setPlayerDataModel(current($result));
    }

    private function setPlayerDataModel(array $data): ?PlayerData
    {
        if (empty($data)) return null;

        return new PlayerData($data["xuid"], PlayerData::jsonToItems($data["inventory"]), PlayerData::jsonToItems($data["armor_inventory"]),
            PlayerData::jsonToItems($data["off_hand_inventory"]), PlayerData::jsonToItems($data["ender_inventory"]), PlayerData::jsonToEffect($data["effect"]),
            $data["health"], $data["hunger"], $data["xp"]);
    }

    public function setPlayerData(PlayerData $data): Generator
    {
        yield from Await::promise(
            fn($resolve, $reject) => $this->pool->getConnection()->executeInsert("coral_reef.player_data.set", ["xuid" => $data->xuid,
                "inv" => json_encode($data->inv), "armor_inv" => json_encode($data->armorInv), "off_hand_inv" => json_encode($data->offHandInv),
                "ender_inv" => json_encode($data->enderInv), "health" => $data->health, "hunger" => $data->hunger, "xp" => $data->xp], $resolve, $reject));
    }

    public function close(): void
    {
    }
}