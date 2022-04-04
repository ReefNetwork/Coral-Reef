<?php
/*
 *  CCCCC                        lll RRRRRR                 fff
 * CC    C  oooo  rr rr    aa aa lll RR   RR   eee    eee  ff
 * CC      oo  oo rrr  r  aa aaa lll RRRRRR  ee   e ee   e ffff
 * CC    C oo  oo rr     aa  aaa lll RR  RR  eeeee  eeeee  ff
 *  CCCCC   oooo  rr      aaa aa lll RR   RR  eeeee  eeeee ff
 *
 * Copyright (c) 2021-2022. Ree-jp(https://ree-jp.net)
 */

namespace ree_jp\coral_reef\form\skill;

use bbo51dog\bboform\element\Button;
use bbo51dog\bboform\element\ClosureButton;
use bbo51dog\bboform\form\ModalForm;
use bbo51dog\bboform\form\SimpleForm;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use ree_jp\coral_reef\account\AccountStore;
use ree_jp\coral_reef\quest\QuestListener;
use ree_jp\coral_reef\skill\BreakSkill;
use ree_jp\coral_reef\skill\SkillManager;

class SkillSettingForm
{
    static function sendForm(AccountStore $store, Player $p): void
    {
        $user = $store->getUser($p->getXuid());
        $nowSkill = is_null($user->skill) ? 'なし' : $user->skill->name;
        $form = (new SimpleForm())
            ->setTitle("Menu -> Skill")
            ->setText("掘る時に発動できるスキルを設定できます\n現在のスキルは $nowSkill です\n")
            ->addElement(new ClosureButton(
                TextFormat::GREEN . "スキルなし", null, function (Player $p) use ($store) {
                self::sendSkillConfirmForm($store, $p, null);
            }));

        foreach (SkillManager::SKILLS as $skillId) {
            $skill = SkillManager::getSkill($skillId);
            if (is_null($skill)) {
                $form->addElement(new Button("不明なスキル"));
            } else {
                // スキルが使えるまら緑色 使えないなら灰色
                $color = $skill->needLevel <= $user->level ? TextFormat::BOLD . TextFormat::GREEN : TextFormat::DARK_GRAY;
                $form->addElement(new ClosureButton(
                    $color . $skill->name . "\n" . $skill->shortDetails, null,
                    function (Player $p) use ($store, $user, $skill) {
                        if ($skill->needLevel <= $user->level) { // レベルが足りる場合
                            self::sendSkillConfirmForm($store, $p, $skill);
                        } else { // レベルが足りない場合
                            $form = new ModalForm(
                                new ClosureButton(
                                    "戻る", null, function (Player $p) use ($store) {
                                    self::sendForm($store, $p);
                                }), new Button("閉じる", null)
                            );
                            $form->setTitle("Skill -> Confirm")
                                ->setText(TextFormat::RED."レベルが足りません\n必要なレベル: " . $skill->needLevel . "\n現在のレベル: " . $user->level);
                            $p->sendForm($form);
                        }
                    }
                ));
            }
        }
        $p->sendForm($form);
    }

    static function sendSkillConfirmForm(AccountStore $store, Player $p, ?BreakSkill $skill): void
    {
        $skillName = is_null($skill) ? 'なし' : $skill->name;
        $coolTime = is_null($skill) ? 0 : $skill->coolTime * 0.05;
        $height = is_null($skill) ? 1 : $skill->height + 1;
        $width = is_null($skill) ? 1 : $skill->width + 1;
        $depth = is_null($skill) ? 1 : $skill->depth + 1;

        $form = new ModalForm(
            new ClosureButton(
                "はい", null, function (Player $p) use ($store, $skillName, $skill) {
                $user = $store->getUser($p->getXuid());
                $user->skill = $skill;
                $p->sendMessage(TextFormat::GREEN." >> スキルを $skillName に変更しました！");
                QuestListener::callSubscribedQuest($p->getXuid(), QuestListener::CHANGE_SKILL, $skillName);
            }),
            new ClosureButton(
                "いいえ", null, function (Player $p) use ($store) {
                self::sendForm($store, $p);
            }),
        );
        $form->setTitle("Skill -> Confirm")
            ->setText("スキルを$skillName に変更しますか?\nクールタイム: $coolTime 秒\n高さ: $height\n幅: $width\n奥行: $depth");
        $p->sendForm($form);
    }
}
