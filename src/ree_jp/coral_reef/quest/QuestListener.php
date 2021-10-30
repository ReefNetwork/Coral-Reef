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

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use ree_jp\coral_reef\quest\data\QuestData;

class QuestListener implements Listener
{
    /**
     * クエスト用のListener
     * イベントの値は絶対に変更しないように
     */

    const JOIN = "join";
    const LEVEL_UP = "level_up";

    static array $subscribeQuest = [];

    /**
     * @priority MONITOR
     */
    function onJoin(PlayerJoinEvent $ev): void
    {
        self::callSubscribedQuest($ev->getPlayer()->getXuid(), self::JOIN, null);
    }

    /**
     * @priority MONITOR
     */
    function onQuit(PlayerQuitEvent $ev): void
    {
        $xuid = $ev->getPlayer()->getXuid();
        // 抜けたらすべてのクエストをunsubscribe
        $quests = [];
        foreach (self::$subscribeQuest as $type => $xuidArray) {
            if (isset($xuidArray[$xuid])) {
                unset($xuidArray[$xuid]);
            }
            $quests[$type] = $xuidArray;
        }
        self::$subscribeQuest = $quests;
    }

    static function callSubscribedQuest(string $xuid, string $type, $value): void
    {
        foreach (self::getSubscribedQuest($xuid, $type) as $quests) {
            foreach ($quests as $quest) {
                if (!$quest instanceof QuestData) continue;
                $quest->onEvent($type, $value);
            }
        }
    }

    static function subscribeQuest(string $xuid, string $type, QuestData $quest): void
    {
        self::$subscribeQuest[$type][$xuid][] = $quest;
    }

    static function unsubscribeQuest(string $xuid, string $type, QuestData $quest): void
    {
        if (isset(self::$subscribeQuest[$type]) && isset(self::$subscribeQuest[$type][$xuid])) {
            foreach (self::$subscribeQuest[$type][$xuid] as $key => $subscribedQuest) {
                if ($quest::ID === $subscribedQuest::ID) unset(self::$subscribeQuest[$type][$xuid][$key]);
            }
        }
    }

    static private function getSubscribedQuest(string $xuid, string $type): array
    {
        if (isset(self::$subscribeQuest[$type]) && isset(self::$subscribeQuest[$type][$xuid])) {
            return self::$subscribeQuest[$type][$xuid];
        }
        return [];
    }
}
