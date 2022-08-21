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
use ree_jp\coral_reef\account\AccountStore;
use ree_jp\coral_reef\quest\data\DailyDigQuest;
use ree_jp\coral_reef\quest\data\DailyLoginQuest;
use ree_jp\coral_reef\quest\data\event\summer_2022\Summer2022DailyDig;
use ree_jp\coral_reef\quest\data\event\summer_2022\Summer2022DailyLogin;
use ree_jp\coral_reef\quest\data\LevelUpQuest;
use ree_jp\coral_reef\quest\data\QuestData;
use ree_jp\coral_reef\quest\data\TutorialQuest;
use ree_jp\coral_reef\quest\data\WeeklyAchieveQuest;
use ree_jp\coral_reef\quest\data\WeeklyDigQuest;
use ree_jp\coral_reef\sql\mysql\SQLRepository;
use ree_jp\coral_reef\sql\SQLConst;

class QuestManager
{
    static array $quests = [];

    static function updateQuests(SQLRepository $repo, AccountStore $store, string $xuid, ?Closure $func = null): void
    {
        $repo->getAllSubtypeValue($xuid, SQLConst::TYPE_QUEST, function (array $rows) use ($store, $repo, $func, $xuid) {
            if (isset(self::$quests[$xuid])) unset(self::$quests[$xuid]);
            foreach ($rows as $row) {
                self::$quests[$xuid][] = self::getQuest($repo, $store, $xuid, $row['subtype'], $row['value']);
            }

            self::addUserQuest($repo, $xuid, TutorialQuest::ID, null);
            self::addUserQuest($repo, $xuid, LevelUpQuest::ID, null, $store);
            self::addUserQuest($repo, $xuid, DailyDigQuest::ID, null);
            self::addUserQuest($repo, $xuid, DailyLoginQuest::ID, null);
            self::addUserQuest($repo, $xuid, WeeklyDigQuest::ID, null);
            self::addUserQuest($repo, $xuid, WeeklyAchieveQuest::ID, null);

            // 期間限定
//            self::addUserQuest($repo, $xuid, Summer2022DailyLogin::ID, null);
//            self::addUserQuest($repo, $xuid, Summer2022DailyDig::ID, null);

            if ($func instanceof Closure) $func($rows);
        });
    }

    /**
     * クエストがユーザーに追加されているか確認し、されていなかったら追加する
     */
    static function addUserQuest(SQLRepository $repo, string $xuid, string $questID, ?string $value, ?AccountStore $store = null): void // クエストがなかったら与える
    {
        foreach (QuestManager::getUserQuests($xuid) as $alreadyQuest) {
            if ($questID === $alreadyQuest::ID) return;
        }
        self::$quests[$xuid][] = self::getQuest($repo, $store, $xuid, $questID, $value);
    }

    static function getQuest(SQLRepository $repo, ?AccountStore $store, string $xuid, string $questID, ?string $value): ?QuestData
    {
        return match ($questID) {
            TutorialQuest::ID => new TutorialQuest($repo, $xuid, $value),
            LevelUpQuest::ID => new LevelUpQuest($repo, $store, $xuid, $value),
            DailyDigQuest::ID => new DailyDigQuest($repo, $xuid, $value),
            DailyLoginQuest::ID => new DailyLoginQuest($repo, $xuid, $value),
            WeeklyDigQuest::ID => new WeeklyDigQuest($repo, $xuid, $value),
            WeeklyAchieveQuest::ID => new WeeklyAchieveQuest($repo, $xuid, $value),

            // 期間限定
            Summer2022DailyLogin::ID => new Summer2022DailyLogin($repo, $xuid, $value),
            Summer2022DailyDig::ID => new Summer2022DailyDig($repo, $xuid, $value),

            default => null,
        };
    }

    static function getUserQuests(string $xuid): array
    {
        return self::$quests[$xuid] ?? [];
    }

    static function save(SQLRepository $repo, string $xuid, ?Closure $func = null): void
    {
        $quests = self::getUserQuests($xuid);
        self::saveQuestLoop($repo, $xuid, $quests, $func);
    }

    private static function saveQuestLoop(SQLRepository $repo, string $xuid, array $quests, ?Closure $lastFunc): void
    {
        $quest = array_shift($quests);
        if (empty($quest)) {
            if (!is_null($lastFunc)) $lastFunc();
            return;
        } else {
            $func = function () use ($repo, $lastFunc, $quests, $xuid): void {
                self::saveQuestLoop($repo, $xuid, $quests, $lastFunc);
            };
        }
        if (!$quest instanceof QuestData) self::saveQuestLoop($repo, $xuid, $quests, $func);
        $repo->setValue($xuid, SQLConst::TYPE_QUEST, $quest::ID, $quest->outputData(), $func, $func);
    }
}
