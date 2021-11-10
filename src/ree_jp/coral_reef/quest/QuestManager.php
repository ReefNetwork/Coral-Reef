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
use ree_jp\coral_reef\quest\data\DigQuest;
use ree_jp\coral_reef\quest\data\LevelUpQuest;
use ree_jp\coral_reef\quest\data\LoginQuest;
use ree_jp\coral_reef\quest\data\QuestData;
use ree_jp\coral_reef\quest\data\TutorialQuest;
use ree_jp\coral_reef\sql\SQLConst;
use ree_jp\coral_reef\sql\SQLManager;

class QuestManager
{
    static array $quests = [];

    static function updateQuests(string $xuid, ?Closure $func = null): void
    {
        SQLManager::$manager->getAllSubtypeValue($xuid, SQLConst::TYPE_QUEST, function (array $rows) use ($func, $xuid) {
            if (isset(self::$quests[$xuid])) unset(self::$quests[$xuid]);
            foreach ($rows as $row) {
                self::$quests[$xuid][] = self::getQuest($xuid, $row['subtype'], $row['value']);
            }
            self::registerQuest($xuid, TutorialQuest::ID, null);
            self::registerQuest($xuid, LoginQuest::ID, null);
            self::registerQuest($xuid, LevelUpQuest::ID, null);
            self::registerQuest($xuid, DigQuest::ID, null);
            if ($func instanceof Closure) $func($rows);
        });
    }

    static function registerQuest(string $xuid, string $questID, ?string $value): void // クエストがなかったら与える
    {
        foreach (QuestManager::getUserQuests($xuid) as $alreadyQuest) {
            if ($questID === $alreadyQuest::ID) return;
        }
        self::$quests[$xuid][] = self::getQuest($xuid, $questID, $value);
    }

    static function getQuest(string $xuid, string $questID, ?string $value): ?QuestData
    {
        switch ($questID) {
            case LevelUpQuest::ID:
                return new LevelUpQuest($xuid, $value);
            case DigQuest::ID:
                return new DigQuest($xuid, $value);
            case LoginQuest::ID:
                return new LoginQuest($xuid, $value);
            default:
                return null;
        }
    }

    static function getUserQuests(string $xuid): array
    {
        return self::$quests[$xuid] ?? [];
    }

    static function save(string $xuid, ?Closure $func = null): void
    {
        $quests = self::getUserQuests($xuid);
        self::saveQuestLoop($xuid, $quests, $func);
    }

    private static function saveQuestLoop(string $xuid, array $quests, ?Closure $lastFunc): void
    {
        $quest = array_shift($quests);
        if (empty($quest)) {
            if (!is_null($lastFunc)) $lastFunc();
            return;
        } else {
            $func = function () use ($lastFunc, $quests, $xuid): void {
                self::saveQuestLoop($xuid, $quests, $lastFunc);
            };
        }
        if (!$quest instanceof QuestData) self::saveQuestLoop($xuid, $quests, $func);
        SQLManager::$manager->setValue($xuid, SQLConst::TYPE_QUEST, $quest::ID, $quest->outputData(), $func, $func);
    }
}
