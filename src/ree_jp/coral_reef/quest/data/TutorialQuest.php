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

use pocketmine\utils\TextFormat;
use ree_jp\coral_reef\account\AccountManager;
use ree_jp\coral_reef\gatya\GatyaManager;
use ree_jp\coral_reef\quest\QuestListener;
use ree_jp\coral_reef\sql\SQLConst;

class TutorialQuest extends QuestData
{
    const ID = "tutorial";
    const NAME = "チュートリアル";
    const SHORT_DETAILS = "サーバーの遊び方を知ろう";
    const EXPLANATION = "サーバーの遊び方を知ろう";

    private int $progress = 0;

    private int $max = 5; // チュートリアル数(今後増やすことも考えて)

    function __construct(string $xuid, ?string $value)
    {
        if (is_null($value)) {
            $type = "0";
        } else {
            $json = json_decode($value, true);
            $type = $json["type"];
            $this->progress = $json["progress"];
        }
        parent::__construct($xuid, $type);
        $this->init();
    }

    private function init(): void
    {
        QuestListener::allUnsubscribeQuest($this->xuid, $this);
        $p = AccountManager::getPlayerByXuid($this->xuid);
        switch (intval($this->value)) {
            case 0:
                QuestListener::subscribeQuest($this->xuid, QuestListener::GET_INIT_TOOL, $this);
                if (!is_null($p)) $p->sendMessage(TextFormat::AQUA . "[チュートリアル]" . $this->getRewardDetails() .
                    "\nロビーワールドで「初期装備」とかかれた看板をタップもしくは右クリックで受け取れます");
                return;
            case 1:
                QuestListener::subscribeQuest($this->xuid, QuestListener::TRANSFER, $this);
                if (!is_null($p)) $p->sendMessage(TextFormat::AQUA . "[チュートリアル]" . $this->getRewardDetails() .
                    "\n棒を地面にタップするとメニューが現れます。メニューの中の「ワールドを移動」を選択し、さらに整地1を選択すると移動できます");
                return;
            case 2:
                return;
        }
    }

    function getRewardDetails(): string
    {
        if ($this->isComplete()) {
            return "完了済み";
        } else {
            return "ガチャ券×1枚";
        }
    }

    function isComplete(): bool
    {
        return intval($this->value) >= $this->max;
    }

    function onEvent(string $type, $value): void
    {
        switch ($type) {
            case QuestListener::GET_INIT_TOOL:
                if (intval($this->value) === 0) {
                    $this->value++;
                    $this->giveReward();
                    $this->init();
                }
                break;
            case QuestListener::TRANSFER:
                if (intval($this->value) === 1) {
                    $this->value++;
                    $this->giveReward();
                    $this->init();
                }
                break;
        }
    }

    private function giveReward(): void
    {
        GatyaManager::addTicket($this->xuid, SQLConst::TICKETS_NORMAL, 1);
        $p = AccountManager::getPlayerByXuid($this->xuid);
        if (!is_null($p)) $p->sendMessage("チュートリアルクエスト報酬としてガチャ券を1枚受け取りました");
    }

    function getProgress(): string
    {
        switch (intval($this->value)) {
            case 0:
                return "初期装備を受け取ろう";
            case 1:
                return "整地1ワールドに移動しよう";
            case 2:
                return "ランダムワープをしよう";
            case 3:
                return "ワープ地点を設定しよう";
            case 4:
                return "ブロックを掘ってみよう";
            case 5:
                return "ガチャを10回引いてみよう";
            default:
                return "チュートリアルはすべて完了しました";
        }
    }

    function outputData(): string
    {
        return json_encode(["type" => $this->value, "progress" => $this->progress]);
    }
}
