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

namespace ree_jp\coral_reef\quest\data;

use pocketmine\Server;
use ree_jp\coral_reef\gatya\GatyaManager;
use ree_jp\coral_reef\quest\QuestListener;
use ree_jp\coral_reef\sql\SQLConst;
use ree_jp\coral_reef\sql\SQLManager;

class DailyLoginQuest extends DailyQuest
{
    const ID = "daily_login";
    const NAME = "ログインボーナス";
    const SHORT_DETAILS = "毎日サーバーにログインして報酬を受け取ろう";
    const EXPLANATION = "サーバーにログインすると報酬が受け取れます。毎日受け取れるので忘れずに受け取りましょう。";

    function __construct(SQLManager $repo, string $xuid, ?string $value)
    {
        parent::__construct($repo, $xuid, $value);
        if ($this->value !== "true") {
            $this->value = "true";
            QuestListener::callSubscribedQuest($this->xuid, QuestListener::CLEAR_QUEST, $this);
            $this->repo->addLog($this->xuid, SQLConst::LOG_QUEST, self::ID, SQLConst::COMPLETE,
                SQLConst::NOW_TIME, null, null);
            GatyaManager::addTicket($this->repo, $this->xuid, SQLConst::TICKETS_NORMAL, 1);
            foreach (Server::getInstance()->getOnlinePlayers() as $p) {
                if ($p->getXuid() === $xuid) $p->sendMessage("ログインボーナスでノーマルガチャチケット×1枚を受け取りました");
            }
        }
    }

    function getRewardDetails(): string
    {
        return "明日ログインするとノーマルガチャチケットが1つ受け取れます";
    }

    function isComplete(): bool
    {
        return true;
    }

    function getProgress(): string
    {
        return "完了済み";
    }
}
