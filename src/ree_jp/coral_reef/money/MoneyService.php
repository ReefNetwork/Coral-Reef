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

use Closure;
use ree_jp\coral_reef\sql\SQLManager;

class MoneyService
{
    static function getMoney(string $xuid, Closure $func): void
    {
        MoneyCache::purge($xuid);
        SQLManager::$manager->getMoney($xuid, function (array $rows) use ($func): void {
            $row = array_shift($rows);
            if (isset($row["money"])) {
                $func($row["money"]);
            } else {
                $func(0);
            }
        });
    }

    static function reduceMoney(string $xuid, int $money): void
    {
        self::addMoney($xuid, -$money);
    }

    static function addMoney(string $xuid, int $money, bool $force = false): void
    {
        if ($force) {
            SQLManager::$manager->addMoney($xuid, $money, null);
        } else {
            if (isset(MoneyCache::$cache[$xuid])) {
                MoneyCache::$cache[$xuid] += $money;
            } else {
                MoneyCache::$cache[$xuid] = $money;
            }
        }
    }
}
