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
use ree_jp\coral_reef\sql\mysql\SQLRepository;

class MoneyService
{
    static function getMoney(SQLRepository $repo, string $xuid, Closure $func): void
    {
        MoneyCache::purge($repo, $xuid, function () use ($func, $xuid, $repo): void {
            $repo->getMoney($xuid, function (array $rows) use ($func): void {
                $row = array_shift($rows);
                if (isset($row["money"])) {
                    $func($row["money"]);
                } else {
                    $func(0);
                }
            });
        });
    }

    static function reduceMoney(SQLRepository $repo, string $xuid, int $money, bool $force = false): void
    {
        self::addMoney($repo, $xuid, -$money, $force);
    }

    static function addMoney(SQLRepository $repo, string $xuid, int $money, bool $force = false): void
    {
        if ($force) {
            $repo->addMoney($xuid, $money, null);
        } else {
            if (isset(MoneyCache::$cache[$xuid])) {
                MoneyCache::$cache[$xuid] += $money;
            } else {
                MoneyCache::$cache[$xuid] = $money;
            }
        }
    }

    static function moneyFormat(float $money): string
    {
        $money = intval($money);
        $digits = ["", "万", "億", "兆", "京"];
        $result = "";
        $counter = 0;
        while ($money > 0) {
            $part = $money % 10000;
            if ($part) {
                $result = $part . $digits[$counter] . $result;
            }
            $money = floor($money / 10000);
            $counter++;
        }
        if ($result == "") $result = "0";
        return $result;
    }
}
