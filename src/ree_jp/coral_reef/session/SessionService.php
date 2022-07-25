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

use Generator;
use pocketmine\Server;
use ree_jp\coral_reef\CoralReefPlugin;
use ree_jp\coral_reef\sql\model\BlockStatisticsModel;
use ree_jp\coral_reef\sql\repo\SessionRepository;
use ree_jp\coral_reef\sql\RepositoryPool;
use ree_jp\coral_reef\StoreHouse;
use ree_jp\reef_edge\ReefEdgePlugin;
use ree_jp\reef_edge\socket\SocketService;
use SOFe\AwaitGenerator\Await;

class SessionService
{
    static function reCreateSession(RepositoryPool $pool, StoreHouse $store, string $xuid): void
    {
        /** @var SessionStore */
        $sessionStore = $store->get(SessionStore::class);

        $sessionStore->destruction($pool, $xuid);
        $sessionStore->createSession($xuid, CoralReefPlugin::$serverID);
    }

    static function sendBetweenRanking(RepositoryPool $pool, ?SessionStore $beforeStore, SessionStore $afterStore, int $measureTime): void
    {
        Await::f2c(function () use ($measureTime, $pool, $afterStore, $beforeStore): Generator {
            /** @var SessionRepository */
            $repo = $pool->get(SessionRepository::class);

            $list = [];

            foreach (Server::getInstance()->getOnlinePlayers() as $p) {
                $beforeSession = $beforeStore?->getSessionData($p->getXuid());
                $afterSession = $afterStore->getSessionData($p->getXuid());

                $isBeforeUse = false;
                $lastLoginTime = $measureTime;

                if ($beforeSession instanceof SessionData) {
                    if ($beforeSession->joinTime === $afterSession->joinTime) {
                        $list[$afterSession->breakCount - $beforeSession->breakCount][] = $p->getName();
                        continue;
                    }

                    $isBeforeUse = true;
                    $lastLoginTime = $beforeSession->joinTime;
                }
                $session = yield from $repo->getCountWithJoin($p->getXuid(), $lastLoginTime, time());
                if (!$session instanceof BlockStatisticsModel) {
                    $list[$afterSession->breakCount][] = $p->getName();
                    continue;
                }

                if ($isBeforeUse) {
                    $list[$afterSession->breakCount + $session->breakCount - $beforeSession->breakCount][] = $p->getName();
                } else {
                    $list[$afterSession->breakCount + $session->breakCount][] = $p->getName();
                }
            }

            $message = "---" . round((time() - $measureTime) / 60, 1) . "分の整地ランキング---(" . CoralReefPlugin::$serverDisplay . "サーバー)---\n";
            $now = 1;
            foreach ($list as $count => $names) {
                if ($now > 5) break;
                $number = 0;
                foreach ($names as $name) {
                    $message .= "$now 位 $name さん($count)\n";
                    $number++;
                }
                $now += $number;
            }
            $message .= "--------------";
            SocketService::sendBroadcastMessage(ReefEdgePlugin::$socketClient, $message);
        });
    }
}