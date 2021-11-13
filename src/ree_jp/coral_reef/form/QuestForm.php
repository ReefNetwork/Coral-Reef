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

use bbo51dog\bboform\element\ClosureButton;
use bbo51dog\bboform\form\SimpleForm;
use pocketmine\Player;
use ree_jp\coral_reef\quest\data\DailyQuest;
use ree_jp\coral_reef\quest\data\QuestData;
use ree_jp\coral_reef\quest\QuestManager;

class QuestForm
{
    static function questForm(Player $p): SimpleForm
    {
        $form = (new SimpleForm())
            ->setTitle("Menu -> Quest")
            ->setText("クエスト一覧です\進行状況を確認できます");
        $finishedForm = (new SimpleForm())
            ->setTitle("Quest -> Finished")
            ->setText("終了したクエスト一覧です");
        foreach (QuestManager::getUserQuests($p->getXuid()) as $quest) {
            if (!$quest instanceof QuestData) continue;
            if ($quest->isComplete() || $quest->isExpired()) {
                $finishedForm->addElement(new ClosureButton(
                    $quest::NAME . "\n" . $quest::SHORT_DETAILS,
                    null,
                    function (Player $p, ClosureButton $button) use ($quest) {
                        $p->sendForm(self::questDetail($quest));
                    }
                ));
            } else {
                $form->addElement(new ClosureButton(
                    $quest::NAME . "\n" . $quest::SHORT_DETAILS,
                    null,
                    function (Player $p, ClosureButton $button) use ($quest) {
                        if (!$quest instanceof QuestData) {
                            $p->sendMessage("エラーが発生しました");
                        }
                        $p->sendForm(self::questDetail($quest));
                    }
                ));
            }
        }
        $form->addElement(new ClosureButton(
            "過去のクエスト一覧",
            null,
            function (Player $p, ClosureButton $button) use ($finishedForm) {
                $p->sendForm($finishedForm);
            }
        ));
        return $form;
    }

    private static function questDetail(QuestData $quest): SimpleForm // クエストの詳細を表示する
    {
        $postScript = "";
        if ($quest instanceof DailyQuest) {
            $postScript = $postScript . "\n期限: " . date("y年m月d日 H時i分", $quest->limit) . "(期限切れ後、進捗がリセットされて復活します)";
        }
        return (new SimpleForm())
            ->setTitle("Quest : " . $quest::NAME)
            ->setText("クエスト詳細: " . $quest::EXPLANATION . "\n進捗: " . $quest->getProgress() . "\n報酬: " . $quest->getRewardDetails() . $postScript)
            ->addElement(
                new ClosureButton(
                    "戻る",
                    null,
                    function (Player $p, ClosureButton $button) {
                        $p->sendForm(self::questForm($p));
                    }
                )
            );
    }
}
