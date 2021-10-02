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
use Frago9876543210\EasyForms\forms\ModalForm;
use pocketmine\Player;
use pocketmine\utils\TextFormat;
use ree_jp\coral_reef\skill\BreakSkill;
use ree_jp\coral_reef\skill\SkillManager;
use ree_jp\coral_reef\sql\SQLManager;

class SkillSelectForm
{
    static function SkillSelectForm(string $xuid): MenuForm
    {
        $user = SQLManager::$manager->getUser($xuid);
        if (is_null($user)) FormManager::messageForm('エラーが発生しました');
        $nowSkill = is_null($user->skill) ? 'なし' : $user->skill->name;
        $buttons = [new Button(TextFormat::GREEN . 'スキルなし')];
        $skills = [null];
        foreach (SkillManager::SKILLS as $skillId) {
            $skill = SkillManager::getSkill($skillId);
            if (is_null($skill)) {
                array_push($buttons, new Button('エラーが発生しました'));
                array_push($skills, null);
            } else {
                if ($skill->needLevel <= $user->level) { // レベルが足りれば緑色にする
                    array_push($buttons, new Button(TextFormat::GREEN . $skill->name));
                } else array_push($buttons, new Button(TextFormat::DARK_GRAY . $skill->name));
                array_push($skills, $skill);
            }
        }
        return new MenuForm('Menu -> Skill', "現在のスキルは$nowSkill です", $buttons,
            function (Player $p, Button $button) use ($user, $skills): void {
                if (array_key_exists($button->getValue(), $skills)) {
                    $skill = $skills[$button->getValue()];
                    if (is_null($skill) || $skill->needLevel <= $user->level) { // スキル無し or レベルが足りる場合
                        $p->sendForm(self::SkillConfirmForm($skill));
                    } else { // レベルが足りない場合
                        $p->sendForm(new ModalForm('Skill -> Confirm',
                            "レベルが足りません\n必要なレベル: " . $skill->needLevel . "\n現在のレベル: " . $user->level,
                            function (Player $p, bool $result) use ($skill): void {
                                $p->sendForm(self::SkillSelectForm($p->getXuid()));
                            }));
                    }
                } else {
                    $p->sendMessage('エラーが発生しました');
                }
            });
    }

    static function SkillConfirmForm(?BreakSkill $skill): ModalForm
    {
        $skillName = is_null($skill) ? 'なし' : $skill->name;
        $coolTime = is_null($skill) ? 0 : $skill->cool_time * 0.05;
        $height = is_null($skill) ? 1 : $skill->height + 1;
        $width = is_null($skill) ? 1 : $skill->width + 1;
        $depth = is_null($skill) ? 1 : $skill->depth + 1;
        return new ModalForm('Skill -> Confirm', "スキルを$skillName に変更しますか?\nクールタイム: $coolTime 秒\n高さ: $height\n幅: $width\n奥行: $depth",
            function (Player $p, bool $result) use ($skill): void {
                if ($result) {
                    $user = SQLManager::$manager->getUser($p->getXuid());
                    $user->skill = $skill;
                    $p->sendMessage('スキルを変更しました');
                } else $p->sendForm(self::skillSelectForm($p->getXuid()));
            });
    }
}
