<?php
/*
 *  CCCCC                        lll RRRRRR                 fff
 * CC    C  oooo  rr rr    aa aa lll RR   RR   eee    eee  ff
 * CC      oo  oo rrr  r  aa aaa lll RRRRRR  ee   e ee   e ffff
 * CC    C oo  oo rr     aa  aaa lll RR  RR  eeeee  eeeee  ff
 *  CCCCC   oooo  rr      aaa aa lll RR   RR  eeeee  eeeee ff
 *
 * Copyright (c) 2021. Ree-jp(https://ree-jp.net)
 */

namespace ree_jp\coral_reef\quest;

use Closure;
use ree_jp\coral_reef\sql\SQLConst;
use ree_jp\coral_reef\sql\SQLManager;

class QuestManager
{
    static array $quests = [];

    static function updateQuests(string $xuid, ?Closure $func = null): void
    {
        SQLManager::$manager->getAllSubtypeValue($xuid, SQLConst::TYPE_QUEST, function (array $rows) use ($func, $xuid) {
            self::$quests[$xuid] = $rows;
            if ($func instanceof Closure) $func($rows);
        });
    }

    static function getQuests(string $xuid): array
    {
        return isset(self::$quests[$xuid]) ? [] : self::$quests[$xuid];
    }

    static function save(string $xuid, ?Closure $func = null): void
    {

    }
}
