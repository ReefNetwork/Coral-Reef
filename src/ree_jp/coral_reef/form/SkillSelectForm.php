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

use bbo51dog\bboform\element\Button;
use bbo51dog\bboform\element\ClosureButton;
use bbo51dog\bboform\form\ModalForm;
use bbo51dog\bboform\form\SimpleForm;
use pocketmine\Player;
use pocketmine\utils\TextFormat;
use ree_jp\coral_reef\skill\BreakSkill;
use ree_jp\coral_reef\skill\SkillManager;
use ree_jp\coral_reef\sql\SQLManager;

class SkillSelectForm
{
    static function SkillSelectForm(string $xuid): SimpleForm
    {
        $user = SQLManager::$manager->getUser($xuid);
        if (is_null($user)) FormManager::messageForm('エラーが発生しました');
        $nowSkill = is_null($user->skill) ? 'なし' : $user->skill->name;
        $buttons = [new Button(TextFormat::GREEN . 'スキルなし')];
        $skills = [null];
        $form = (new SimpleForm())
            ->setTitle("Menu -> Skill")
            ->setText("現在のスキルは $nowSkill です")
            ->addElement(new ClosureButton(
                TextFormat::GREEN . "スキルなし",
                null,
                function (Player $p, ClosureButton $button) {

                }
            ));
        foreach (SkillManager::SKILLS as $skillId) {
            $skill = SkillManager::getSkill($skillId);
            if (is_null($skill)) {
                $form->addElement(new Button('エラーが発生しました'));
                array_push($skills, null);
            } else {
                $color = $skill->needLevel <= $user->level ? TextFormat::GREEN : TextFormat::DARK_GRAY;
                $form->addElement(new ClosureButton(
                    $color . $skill->name,
                    null,
                    function (Player $p, ClosureButton $button) use ($user, $skill) {
                        if (is_null($skill) || $skill->needLevel <= $user->level) { // スキル無し or レベルが足りる場合
                            $p->sendForm(self::SkillConfirmForm($skill));
                        } else { // レベルが足りない場合
                            $p->sendForm((new ModalForm(
                                new ClosureButton(
                                    "戻る",
                                    null,
                                    function (Player $p, ClosureButton $button) {
                                        $p->sendForm(self::SkillSelectForm($p->getXuid()));
                                    }
                                ),
                                new Button("閉じる", null)
                            ))
                                ->setTitle("Skill -> Confirm")
                                ->setText("レベルが足りません\n必要なレベル: " . $skill->needLevel . "\n現在のレベル: " . $user->level)
                            );
                        }
                    }
                ));
                array_push($skills, $skill);
            }
        }
        return $form;
    }

    static function SkillConfirmForm(?BreakSkill $skill): ModalForm
    {
        $skillName = is_null($skill) ? 'なし' : $skill->name;
        $coolTime = is_null($skill) ? 0 : $skill->cool_time * 0.05;
        $height = is_null($skill) ? 1 : $skill->height + 1;
        $width = is_null($skill) ? 1 : $skill->width + 1;
        $depth = is_null($skill) ? 1 : $skill->depth + 1;
        return (new ModalForm(
            new ClosureButton(
                "はい",
                null,
                function (Player $p, ClosureButton $button) use ($skill) {
                    $user = SQLManager::$manager->getUser($p->getXuid());
                    $user->skill = $skill;
                    $p->sendMessage('スキルを変更しました');
                }
            ),
            new ClosureButton(
                "いいえ",
                null,
                function (Player $p, ClosureButton $button) {
                    $p->sendForm(self::SkillSelectForm($p->getXuid()));
                }
            ),
        ))
            ->setTitle("Skill -> Confirm")
            ->setText("スキルを$skillName に変更しますか?\nクールタイム: $coolTime 秒\n高さ: $height\n幅: $width\n奥行: $depth");
    }
}
