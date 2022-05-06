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

use JetBrains\PhpStorm\Pure;
use ree_jp\coral_reef\account\AccountService;
use ree_jp\coral_reef\gatya\GatyaManager;
use ree_jp\coral_reef\quest\QuestListener;
use ree_jp\coral_reef\sql\mysql\SQLRepository;
use ree_jp\coral_reef\sql\SQLConst;

class WeeklyDigQuest extends WeeklyQuest
{
    const ID = "weekly_dig";
    const NAME = "整地(毎週)";
    const SHORT_DETAILS = "整地しよう!(毎週)";
    const EXPLANATION = "スキルを一定回数使用して整地をしよう。";

    function __construct(SQLRepository $repo, string $xuid, ?string $value)
    {
        parent::__construct($repo, $xuid, $value);
        if (!$this->isComplete()) {
            QuestListener::subscribeQuest($xuid, QuestListener::USE_SKILL, $this);
        }
    }

    function isComplete(): bool
    {
        return $this->value >= 10000;
    }

    function onEvent(string $type, $value): void
    {
        switch ($type) {
            case QuestListener::USE_SKILL:
                $this->value++;
                break;
        }
        switch (intval($this->value)) {
            case 10000:
                QuestListener::unsubscribeQuest($this->xuid, QuestListener::USE_SKILL, $this);
                QuestListener::callSubscribedQuest($this->xuid, QuestListener::CLEAR_QUEST, $this);
                $this->repo->addLog($this->xuid, SQLConst::LOG_QUEST, self::ID, $this->value,
                    SQLConst::NOW_TIME, null, null);
                GatyaManager::addTicket($this->repo, $this->xuid, SQLConst::TICKETS_NORMAL, 3);
                $p = AccountService::getPlayerByXuid($this->xuid);
                if (!is_null($p)) $p->sendMessage("デイリー整地ボーナスとしてガチャチケットを受け取りました");
                break;
        }
    }

    function getProgress(): string
    {
        switch (true) {
            case intval($this->value) < 10000:
                return "スキルを1万回発動させよう (" . $this->value . "/10000)";
        }
        return "完了";
    }

    #[Pure] function getRewardDetails(): string
    {
        if ($this->isComplete()) {
            return "完了済み";
        } else {
            return "ガチャチケット×3枚";
        }
    }
}
