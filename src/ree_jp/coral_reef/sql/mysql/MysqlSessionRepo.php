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
use ree_jp\coral_reef\session\SessionData;
use ree_jp\coral_reef\sql\model\BlockStatisticsModel;
use ree_jp\coral_reef\sql\repo\SessionRepository;
use ree_jp\coral_reef\sql\RepositoryPool;
use ree_jp\coral_reef\sql\SQLConst;
use SOFe\AwaitGenerator\Await;

class MysqlSessionRepo implements SessionRepository
{
    public function __construct(private RepositoryPool $pool, bool $isInit)
    {
        if ($isInit) {
            $pool->getConnection()->executeGeneric("coral_reef.init.tables.session");
        }
    }

    public function addSession(string $xuid, SessionData $session): Generator
    {
        yield from Await::promise(
            fn($resolve, $reject) => $this->pool->getConnection()->executeGeneric("coral_reef.session.add", ["xuid" => intval($xuid), "server" => $session->server,
                "join_time" => date(SQLConst::DATE_FORMAT, $session->joinTime), "quit_time" => date(SQLConst::DATE_FORMAT, $session->quitTime),
                "break_count" => $session->breakCount, "place_count" => $session->placeCount, "skill_count" => $session->skillCount]));
    }

    public function getRecentSession(string $xuid): Generator
    {
        $result = yield from Await::promise(
            fn($resolve, $reject) => $this->pool->getConnection()->executeSelect("coral_reef.session.get_recent",
                ["xuid" => intval($xuid)], $resolve, $reject));
        return current($this->setSessionModels($result));
    }

    public function getAllCountWithJoin(int $firstTime, int $lastTime): Generator
    {
        $result = yield from Await::promise(
            fn($resolve, $reject) => $this->pool->getConnection()->executeSelect("coral_reef.session.all_get_count_join_between_sort_desc",
                ["first_time" => date(SQLConst::DATE_FORMAT, $firstTime), "last_time" => date(SQLConst::DATE_FORMAT, $lastTime)], $resolve, $reject));
        return $this->setBlockStatisticsModels($result);
    }

    /**
     * @param array $data
     * @return SessionData[]
     */
    private function setSessionModels(array $data): array
    {
        if (empty($data)) return [];

        $sessions = [];
        foreach ($data as $sessionRaw) {
            $session = new SessionData(strval($sessionRaw["xuid"]), $sessionRaw["server"]);
            $session->joinTime = strtotime($sessionRaw["join_time"]);
            $session->quitTime = strtotime($sessionRaw["quit_time"]);
            $session->breakCount = $sessionRaw["break_count"];
            $session->placeCount = $sessionRaw["place_count"];
            $session->skillCount = $sessionRaw["skill_count"];
            $sessions[] = $session;
        }
        return $sessions;
    }

    /**
     * @param array $data
     * @return BlockStatisticsModel[]
     */
    private function setBlockStatisticsModels(array $data): array
    {
        if (empty($data)) return [];

        $statistics = [];
        foreach ($data as $statisticsRaw) {
            $statistics[] = new BlockStatisticsModel(strval($statisticsRaw["xuid"]), $statisticsRaw["break_count"], $statisticsRaw["place_count"], $statisticsRaw["skill_count"]);
        }
        return $statistics;
    }

    public function close(): void
    {
    }
}