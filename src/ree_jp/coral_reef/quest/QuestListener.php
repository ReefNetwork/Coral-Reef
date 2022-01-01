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

use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\entity\EntityTeleportEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\player\Player;
use ree_jp\coral_reef\quest\data\QuestData;

class QuestListener implements Listener
{
    /**
     * クエスト用のListener
     * イベントの値は絶対に変更しないように
     */

    const BREAK = "break";
    const TRANSFER = "transfer";

    const LEVEL_UP = "level_up";
    const CHANGE_SKILL = "change_skill";
    const USE_SKILL = "user_skill";
    const GET_INIT_TOOL = "get_init_tool";
    const RANDOM_WARP = "random_warp";
    const CREATE_WARP_POINT = "create_warp_point";
    const GATYA = "gatya";
    const CLEAR_QUEST = "clear_quest";

    static array $subscribeQuest = [];

    /**
     * @priority MONITOR
     */
    function onBreak(BlockBreakEvent $ev): void
    {
        QuestListener::callSubscribedQuest($ev->getPlayer()->getXuid(), self::BREAK, $ev->getBlock());
    }

    /**
     * @priority MONITOR
     */
    function onTransfer(EntityTeleportEvent $ev): void
    {
        $p = $ev->getEntity();
        if ($p instanceof Player) {
            QuestListener::callSubscribedQuest($p->getXuid(), self::TRANSFER, $ev->getTo()->getWorld()->getFolderName());
        }
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
        foreach (self::getSubscribedQuest($xuid, $type) as $quest) {
            if (!$quest instanceof QuestData) continue;
            $quest->onEvent($type, $value);
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

    static function allUnsubscribeQuest(string $xuid, QuestData $quest): void
    {
        foreach (self::$subscribeQuest as $type => $xuidArray) {
            if (isset($xuidArray[$xuid])) {
                foreach (self::$subscribeQuest[$type][$xuid] as $key => $subscribedQuest) {
                    if ($quest::ID === $subscribedQuest::ID) unset(self::$subscribeQuest[$type][$xuid][$key]);
                }
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
