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

use ree_jp\coral_reef\account\AccountManager;
use ree_jp\coral_reef\gatya\GatyaManager;
use ree_jp\coral_reef\quest\QuestListener;
use ree_jp\coral_reef\sql\SQLConst;

class DigQuest extends DailyQuest
{
    const ID = "dig";
    const NAME = "整地";
    const SHORT_DETAILS = "整地しよう!";
    const EXPLANATION = "スキルを一定回数使用して整地をしよう。";

    function __construct(string $xuid, ?string $value)
    {
        parent::__construct($xuid, $value);
        if (!$this->isComplete()) {
            QuestListener::subscribeQuest($xuid, QuestListener::USE_SKILL, $this);
        }
    }

    function isComplete(): bool
    {
        return $this->value >= 1000;
    }

    function onEvent(string $type, $value): void
    {
        switch ($type) {
            case QuestListener::USE_SKILL:
                $this->value++;
                switch (intval($this->value)) {
                    /** @noinspection PhpMissingBreakStatementInspection */
                    case 1000:
                        QuestListener::unsubscribeQuest($this->xuid, QuestListener::USE_SKILL, $this);
                    case 100:
                        GatyaManager::addTicket($this->xuid, SQLConst::TICKETS_NORMAL, 1);
                        $p = AccountManager::getPlayerByXuid($this->xuid);
                        if (!is_null($p)) $p->sendMessage("デイリー整地ボーナスとしてガチャチケットを受け取りました");
                        break;
                }
                break;
        }
    }

    function getProgress(): string
    {
        switch (true) {
            case intval($this->value) < 100:
                return "スキルを100回発動させよう (" . $this->value . "/100)";
            case intval($this->value) < 1000:
                return "スキルを1000回発動させよう (" . $this->value . "/1000)";
        }
        return parent::getProgress();
    }

    function getRewardDetails(): string
    {
        return "ガチャチケット×1枚";
    }
}
