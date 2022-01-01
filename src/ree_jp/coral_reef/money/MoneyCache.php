<?php
/*
 *  CCCCC                        lll RRRRRR                 fff
 * CC    C  oooo  rr rr    aa aa lll RR   RR   eee    eee  ff
 * CC      oo  oo rrr  r  aa aaa lll RRRRRR  ee   e ee   e ffff
 * CC    C oo  oo rr     aa  aaa lll RR  RR  eeeee  eeeee  ff
 *  CCCCC   oooo  rr      aaa aa lll RR   RR  eeeee  eeeee ff
 *
 * Copyright (c) 2021-2021. Ree-jp(https://ree-jp.net)
 */

namespace ree_jp\coral_reef\money;

use ree_jp\coral_reef\sql\SQLManager;

/**
 * 1回のスキル発動で数十回呼び出されることを考慮した簡易的なキャッシュ
 */
class MoneyCache
{
    static array $cache = [];

    static function purgeAll(SQLManager $repo): void
    {
        foreach (self::$cache as $xuid => $money) {
            self::purge($repo, $xuid);
        }
    }

    static function purge(SQLManager $repo, string $xuid): void
    {
        if (isset(self::$cache[$xuid])) {
            $repo->addMoney($xuid, self::$cache[$xuid], null);
            unset(self::$cache[$xuid]);
        }
    }
}
