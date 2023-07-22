<?php
/*
 *  CCCCC                        lll RRRRRR                 fff
 * CC    C  oooo  rr rr    aa aa lll RR   RR   eee    eee  ff
 * CC      oo  oo rrr  r  aa aaa lll RRRRRR  ee   e ee   e ffff
 * CC    C oo  oo rr     aa  aaa lll RR  RR  eeeee  eeeee  ff
 *  CCCCC   oooo  rr      aaa aa lll RR   RR  eeeee  eeeee ff
 *
 * Copyright (c) 2023. Ree-jp(https://ree-jp.net)
 */

namespace ree_jp\coral_reef\sql\mysql;

use Generator;
use ree_jp\coral_reef\sql\model\LogData;
use ree_jp\coral_reef\sql\repo\LogRepository;
use ree_jp\coral_reef\sql\RepositoryPool;
use ree_jp\coral_reef\sql\SQLConst;
use SOFe\AwaitGenerator\Await;

class MysqlLogRepo implements LogRepository
{
    public function __construct(private RepositoryPool $pool, bool $isInit)
    {
        if ($isInit) {
            $this->pool->getConnection()->executeGeneric("coral_reef.init.tables.log");
        }
    }

    public function addLog(LogData $data): Generator
    {
        yield from Await::promise(
            fn($resolve, $reject) => $this->pool->getConnection()->executeInsert("coral_reef.log.add", ["xuid" => intval($data->xuid), "type" => $data->type,
                "subtype" => $data->subtype, "value" => $data->value, "time" => date(SQLConst::DATE_FORMAT, $data->time)], $resolve, $reject));
    }

    /**
     * @param string $xuid
     * @param string $type
     * @return Generator LogData[]
     */
    public function getLogNewer(string $xuid, string $type): Generator
    {
        $result = yield from Await::promise(fn($resolve, $reject) => $this->pool->getConnection()->executeSelect("coral_reef.log.get.type_sort_newest",
            ["xuid" => intval($xuid), "type" => $type], $resolve, $reject));
        if (!$result) return [];
        return $this->setLogDataModel($result);
    }

    private function setLogDataModel(array $data): array
    {
        $logs = [];
        foreach ($data as $raw) {
            $logs[] = new LogData(strval($raw["xuid"]), $raw["type"], $raw["subtype"] ?? null, $raw["value"], strtotime($raw["time"]));
        }
        return $logs;
    }

    public function close(): void
    {
    }
}
