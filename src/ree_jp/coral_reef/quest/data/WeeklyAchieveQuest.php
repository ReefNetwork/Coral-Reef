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

class WeeklyAchieveQuest extends WeeklyQuest
{
    const ID = "weekly_achieve";
    const NAME = "デイリークエスト";
    const SHORT_DETAILS = "デイリークエストをクリアしよう";
    const EXPLANATION = "毎日サーバーをプレイしてデイリークエストをクリアしよう";

    function __construct(SQLRepository $repo, string $xuid, ?string $value)
    {
        parent::__construct($repo, $xuid, $value);
        if (!$this->isComplete()) {
            QuestListener::subscribeQuest($xuid, QuestListener::CLEAR_QUEST, $this);
        }
    }

    function isComplete(): bool
    {
        return $this->value >= 21;
    }

    function onEvent(string $type, $value): void
    {
        switch ($type) {
            case QuestListener::CLEAR_QUEST:
                if ($value instanceof DailyQuest) {
                    $this->value++;
                }
                break;
        }
        switch (intval($this->value)) {
            /** @noinspection PhpMissingBreakStatementInspection */
            case 21:
                QuestListener::unsubscribeQuest($this->xuid, QuestListener::CLEAR_QUEST, $this);
            case 14:
                QuestListener::callSubscribedQuest($this->xuid, QuestListener::CLEAR_QUEST, $this);
                $this->repo->addLog($this->xuid, SQLConst::LOG_QUEST, self::ID, $this->value,
                    SQLConst::NOW_TIME, null, null);
                GatyaManager::addTicket($this->repo, $this->xuid, SQLConst::TICKETS_NORMAL, 3);
                $p = AccountService::getPlayerByXuid($this->xuid);
                if (!is_null($p)) $p->sendMessage("デイリークエスト達成報酬としてガチャチケットを受け取りました");
                break;
        }
    }

    function getProgress(): string
    {
        return match (true) {
            intval($this->value) < 14 => "クエストを14回クリアしよう (" . $this->value . "/14)",
            intval($this->value) < 21 => "クエストを21回クリアしよう (" . $this->value . "/21)",
            default => "完了",
        };
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
