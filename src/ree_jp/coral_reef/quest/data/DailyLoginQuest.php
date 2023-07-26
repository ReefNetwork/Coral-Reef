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
use ree_jp\coral_reef\CoralReefPlugin;
use ree_jp\coral_reef\gatya\GatyaManager;
use ree_jp\coral_reef\quest\QuestListener;
use ree_jp\coral_reef\sql\model\LogData;
use ree_jp\coral_reef\sql\mysql\SQLRepository;
use ree_jp\coral_reef\sql\repo\LogRepository;
use ree_jp\coral_reef\sql\SQLConst;

class DailyLoginQuest extends DailyQuest
{
    const ID = "daily_login";
    const NAME = "ログインボーナス";
    const SHORT_DETAILS = "毎日サーバーにログインして報酬を受け取ろう";
    const EXPLANATION = "サーバーにログインすると報酬が受け取れます。毎日受け取れるので忘れずに受け取りましょう。";

    const BONUS_TICKET = SQLConst::TICKETS_NORMAL;
    const BONUS_TICKET_NAME = "ノーマルガチャチケット";

    function __construct(SQLRepository $repo, string $xuid, ?string $value)
    {
        parent::__construct($repo, $xuid, $value);
        if ($this->value !== "true") {
            $this->value = "true";
            QuestListener::callSubscribedQuest($this->xuid, QuestListener::CLEAR_QUEST, $this);
            /** @var LogRepository $repo */
            $repo = CoralReefPlugin::$plugin->pool->get(LogRepository::class);
            $repo->addLog(LogData::create($this->xuid, SQLConst::LOG_QUEST, static::ID, SQLConst::COMPLETE));
            GatyaManager::addTicket($this->repo, $this->xuid, static::BONUS_TICKET, 1);
            foreach (Server::getInstance()->getOnlinePlayers() as $p) {
                if ($p->getXuid() === $xuid) $p->sendMessage("ログインボーナスで" . static::BONUS_TICKET_NAME . "×1枚を受け取りました");
            }
        }
    }

    function getRewardDetails(): string
    {
        return "明日ログインすると" . static::BONUS_TICKET_NAME . "が1つ受け取れます";
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
