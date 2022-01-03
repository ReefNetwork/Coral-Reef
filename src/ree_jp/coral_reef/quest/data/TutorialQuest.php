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
use pocketmine\utils\TextFormat;
use ree_jp\coral_reef\account\AccountService;
use ree_jp\coral_reef\gatya\GatyaManager;
use ree_jp\coral_reef\quest\QuestListener;
use ree_jp\coral_reef\sql\SQLConst;
use ree_jp\coral_reef\sql\SQLRepository;

class TutorialQuest extends QuestData
{
    const ID = "tutorial";
    const NAME = "チュートリアル";
    const SHORT_DETAILS = "サーバーの遊び方を知ろう";
    const EXPLANATION = "サーバーの遊び方を知ろう";

    private int $progress = 0;

    private int $max = 6; // チュートリアル数(今後増やすことも考えて)

    function __construct(SQLRepository $repo, string $xuid, ?string $value)
    {
        if (is_null($value)) {
            $type = "0";
        } else {
            $json = json_decode($value, true);
            $type = $json["type"];
            $this->progress = $json["progress"];
        }
        parent::__construct($repo, $xuid, $type);
        $this->init();
    }

    private function init(): void
    {
        QuestListener::allUnsubscribeQuest($this->xuid, $this);
        $p = AccountService::getPlayerByXuid($this->xuid);
        switch (intval($this->value)) {
            case 0:
                QuestListener::subscribeQuest($this->xuid, QuestListener::GET_INIT_TOOL, $this);
                if (!is_null($p)) $p->sendMessage(TextFormat::AQUA . "[チュートリアル]" . $this->getProgress() .
                    "\nロビーワールドで「初期装備」とかかれた看板をタップもしくは右クリックで受け取れます");
                return;
            case 1:
                QuestListener::subscribeQuest($this->xuid, QuestListener::TRANSFER, $this);
                if (!is_null($p)) $p->sendMessage(TextFormat::AQUA . "[チュートリアル]" . $this->getProgress() .
                    "\n棒を地面にタップするとメニューが現れます。メニューの中の「ワールドを移動」を選択し、さらに整地1を選択すると移動できます");
                return;
            case 2:
                QuestListener::subscribeQuest($this->xuid, QuestListener::RANDOM_WARP, $this);
                if (!is_null($p)) $p->sendMessage(TextFormat::AQUA . "[チュートリアル]" . $this->getProgress() .
                    "\nメニューを開いて「ランダムワープ」を選択するとランダムな地点にワープ出来ます");
                return;
            case 3:
                QuestListener::subscribeQuest($this->xuid, QuestListener::CREATE_WARP_POINT, $this);
                if (!is_null($p)) $p->sendMessage(TextFormat::AQUA . "[チュートリアル]" . $this->getProgress() .
                    "\nメニューを開いて「ワープ地点」を選択し、さらに「ワープ地点を 作成/削除 する」を選択し、さらに「ワープ地点を作成する」を選択し、" .
                    "分かりやすいワープ地点の名前を入力するとワープ地点を作成できます");
                return;
            case 4:
                QuestListener::subscribeQuest($this->xuid, QuestListener::BREAK, $this);
                if (!is_null($p)) $p->sendMessage(TextFormat::AQUA . "[チュートリアル]" . $this->getProgress() .
                    "\nブロックを掘ってみよう");
                return;
            case 5:
                QuestListener::subscribeQuest($this->xuid, QuestListener::CHANGE_SKILL, $this);
                if (!is_null($p)) $p->sendMessage(TextFormat::AQUA . "[チュートリアル]" . $this->getProgress() .
                    "\nメニューを開いて「スキル設定」を選択し、さらに「" . TextFormat::GREEN . "アングリア" . TextFormat::RESET . "」選択するとスキルを変更できます");
                return;
            case 6:
                QuestListener::subscribeQuest($this->xuid, QuestListener::GATYA, $this);
                if (!is_null($p)) $p->sendMessage(TextFormat::AQUA . "[チュートリアル]" . $this->getProgress() .
                    "\nメニューから「ガチャ」を選択し、「ノーマルガチャ 10連続」を選択するとガチャを10回引くことができます");
                return;
        }
    }

    #[Pure] function getRewardDetails(): string
    {
        if ($this->isComplete()) {
            return "完了済み";
        } else {
            return "ガチャ券×1枚";
        }
    }

    function isComplete(): bool
    {
        return intval($this->value) > $this->max;
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
                if (($value === "main_1") && (intval($this->value) === 1)) {
                    $this->value++;
                    $this->giveReward();
                    $this->init();
                }
                break;
            case QuestListener::RANDOM_WARP:
                if (intval($this->value) === 2) {
                    $this->value++;
                    $this->giveReward();
                    $this->init();
                }
                break;
            case QuestListener::CREATE_WARP_POINT:
                if (intval($this->value) === 3) {
                    $this->value++;
                    $this->giveReward();
                    $this->init();
                }
                break;
            case QuestListener::BREAK:
                if (intval($this->value) === 4) {
                    $this->value++;
                    $this->giveReward();
                    $this->init();
                }
                break;
            case QuestListener::CHANGE_SKILL:
                if (intval($this->value) === 5) {
                    $this->value++;
                    $this->giveReward();
                    $this->init();
                }
                break;
            case QuestListener::GATYA:
                if (intval($this->value) === 6) {
                    $this->progress++;
                    if ($this->progress >= 10) {
                        $this->value++;
                        $this->giveReward();
                        $this->progress = 0;
                        $this->init();
                    }
                }
                break;
        }
    }

    private function giveReward(): void
    {
        QuestListener::callSubscribedQuest($this->xuid, QuestListener::CLEAR_QUEST, $this);
        GatyaManager::addTicket($this->repo, $this->xuid, SQLConst::TICKETS_NORMAL, 1);
        $p = AccountService::getPlayerByXuid($this->xuid);
        if (!is_null($p)) $p->sendMessage("チュートリアルクエスト報酬としてガチャ券を1枚受け取りました");
    }

    function getProgress(): string
    {
        return match (intval($this->value)) {
            0 => "初期装備を受け取ろう",
            1 => "整地1ワールドに移動しよう",
            2 => "ランダムワープをしよう",
            3 => "ワープ地点を設定しよう",
            4 => "ブロックを掘ってみよう",
            5 => "スキルを設定してみよう",
            6 => "ガチャを10回引いてみよう(" . $this->progress . "/10)",
            default => "チュートリアルはすべて完了しました",
        };
    }

    function outputData(): string
    {
        return json_encode(["type" => $this->value, "progress" => $this->progress]);
    }
}
