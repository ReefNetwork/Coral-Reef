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

namespace ree_jp\coral_reef\account;

use ree_jp\coral_reef\session\SessionService;
use ree_jp\coral_reef\session\SessionStore;
use ree_jp\coral_reef\sql\RepositoryPool;
use ree_jp\coral_reef\StoreHouse;
use ree_jp\reef_edge\ReefEdgePlugin;
use ree_jp\reef_edge\socket\SocketService;
use SOFe\AwaitGenerator\AwaitException;

class BetweenRanking
{
    private SessionStore $beforeStore;
    private int $measureTime;

    public function __construct(private RepositoryPool $pool, private StoreHouse $store)
    {
        /** @var SessionStore $sessionStore */
        $sessionStore = $store->get(SessionStore::class);
        $this->beforeStore = clone $sessionStore;
        $this->measureTime = time();
    }

    public function showRanking(): void
    {
        /** @var SessionStore $newSessionStore */
        $newSessionStore = $this->store->get(SessionStore::class);
        try {
            SessionService::sendBetweenRanking($this->pool, $this->beforeStore, $newSessionStore, $this->measureTime);
        } catch (AwaitException $ex) {
            SocketService::sendBroadcastMessage(ReefEdgePlugin::$socketClient, "ランキング表示中にエラーが発生しました" . $ex->getMessage());
        }
        $this->beforeStore = clone $newSessionStore;
        $this->measureTime = time();
    }
}
