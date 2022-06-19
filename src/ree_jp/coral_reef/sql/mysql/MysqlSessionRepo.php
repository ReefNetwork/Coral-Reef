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
            fn($resolve, $reject) => $this->pool->getConnection()->executeGeneric("coral_reef.session.add", ["xuid" => $xuid, "server" => $session->server,
                "join_time" => date(SQLConst::DATE_FORMAT, $session->joinTime), "quit_time" => date(SQLConst::DATE_FORMAT, $session->quitTime),
                "break_count" => $session->breakCount, "place_count" => $session->placeCount, "skill_count" => $session->skillCount]));
    }

    public function getRecentSession(string $xuid): Generator
    {
        $result = yield from Await::promise(
            fn($resolve, $reject) => $this->pool->getConnection()->executeSelect("coral_reef.session.get_recent",
                ["xuid" => $xuid], $resolve, $reject));
        return $this->setSessionModel($result);
    }

    private function setSessionModel(array $data): ?SessionData
    {
        if (empty($data)) return null;
        $session = new SessionData($data["xuid"], $data["server"]);
        $session->joinTime = strtotime($data["join_time"]);
        $session->quitTime = strtotime($data["quit_time"]);
        $session->breakCount = $data["break_count"];
        $session->placeCount = $data["place_count"];
        $session->skillCount = $data["skill_count"];
        return $session;
    }

    public function close(): void
    {
    }
}