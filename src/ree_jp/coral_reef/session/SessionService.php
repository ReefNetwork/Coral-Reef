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

namespace ree_jp\coral_reef\session;

use pocketmine\Server;
use ree_jp\coral_reef\CoralReefPlugin;
use ree_jp\coral_reef\sql\repo\SessionRepository;
use ree_jp\coral_reef\sql\RepositoryPool;
use ree_jp\coral_reef\StoreHouse;
use ree_jp\reef_edge\ReefEdgePlugin;
use ree_jp\reef_edge\socket\SocketService;

class SessionService
{
    static function reCreateSession(RepositoryPool $pool, StoreHouse $store, string $xuid): void
    {
        /** @var SessionStore */
        $sessionStore = $store->get(SessionStore::class);

        $sessionStore->destruction($pool, $xuid);
        $sessionStore->createSession($xuid, CoralReefPlugin::$serverID);
    }

    static function sendBetweenRanking(RepositoryPool $pool, SessionStore $beforeStore, SessionStore $afterStore, int $measureTime): void
    {
        /** @var SessionRepository */
        $repo = $pool->get(SessionRepository::class);

        foreach (Server::getInstance()->getOnlinePlayers() as $p) {
            $beforeSession = $beforeStore->getSessionData($p->getXuid());
            $afterSession = $afterStore->getSessionData($p->getXuid());

            $brockBreak = $afterSession->breakCount;
            $lastLoginTime = $measureTime;

            if ($beforeStore !== null) {
                if ($beforeSession->joinTime === $afterSession->joinTime) {
                    $brockBreak = $afterSession->breakCount - $beforeSession->breakCount;
                    continue;
                } else {
                    $lastLoginTime = $beforeSession->quitTime;
                }
            }
            $repo->getAllCountWithJoin($lastLoginTime, time());
        }
        SocketService::sendBroadcastMessage(ReefEdgePlugin::$socketClient, "");
    }
}