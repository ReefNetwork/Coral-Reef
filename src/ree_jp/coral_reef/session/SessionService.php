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

use ree_jp\coral_reef\CoralReefPlugin;
use ree_jp\coral_reef\sql\RepositoryPool;
use ree_jp\coral_reef\StoreHouse;

class SessionService
{
    static function reCreateSession(RepositoryPool $pool, StoreHouse $store, string $xuid): void
    {
        /** @var SessionStore */
        $sessionStore = $store->get(SessionStore::class);

        $sessionStore->destruction($pool, $xuid);
        $sessionStore->createSession($xuid, CoralReefPlugin::$serverID);
    }
}