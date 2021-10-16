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

namespace ree_jp\coral_reef\form;

use Frago9876543210\EasyForms\elements\Button;
use Frago9876543210\EasyForms\forms\MenuForm;
use pocketmine\Player;
use ree_jp\coral_reef\quest\data\QuestData;
use ree_jp\coral_reef\quest\QuestManager;

class QuestForm
{
    static function questForm(Player $p): MenuForm
    {
        $buttons = [];
        $nowQuest = [];
        $finishQuest = [];
        foreach (QuestManager::getUserQuests($p->getXuid()) as $quest) {
            if (!$quest instanceof QuestData) continue;
            if ($quest->isComplete() || $quest->isExpired()) {
                $finishQuest[] = $quest;
            } else {
                $buttons[] = new Button($quest::NAME . "\n" . $quest::SHORT_DETAILS);
                $nowQuest[] = $quest;
            }
        }
        $buttons[] = new Button("過去のクエスト一覧");
        return new MenuForm("Menu -> Quest", "クエスト一覧です\進行状況を確認できます", $buttons,
            function (Player $p, Button $button) use ($finishQuest, $nowQuest): void {
                if (isset($nowQuest[$button->getValue()])) {
                    $quest = $nowQuest[$button->getValue()];
                    if (!$quest instanceof QuestData) {
                        $p->sendMessage("エラーが発生しました");
                    }
                    $p->sendForm(self::questDetail($quest));
                } else {
                    $buttons = [];
                    foreach ($finishQuest as $quest) {
                        $buttons[] = new Button($quest::NAME . "\n" . $quest::SHORT_DETAILS);
                    }
                    $p->sendForm(new MenuForm("Quest -> Finished", "終了したクエスト一覧です", $buttons,
                        function (Player $p, Button $button) use ($finishQuest, $nowQuest): void {
                            if (isset($nowQuest[$button->getValue()])) {
                                $quest = $nowQuest[$button->getValue()];
                                if (!$quest instanceof QuestData) {
                                    $p->sendMessage("エラーが発生しました");
                                }
                                $p->sendForm(self::questDetail($quest));
                            } else $p->sendMessage("エラーが発生しました");
                        }));
                }
            });
    }

    private static function questDetail(QuestData $quest): MenuForm // クエストの詳細を表示する
    {
        return new MenuForm("Quest : " . $quest::NAME, "クエスト詳細: " . $quest::EXPLANATION . "\n報酬: " . $quest->getRewardDetails()
            . "\n期限: " . "");
    }
}
