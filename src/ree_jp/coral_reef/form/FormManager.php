<?php
/*
 *  CCCCC                        lll RRRRRR                 fff
 * CC    C  oooo  rr rr    aa aa lll RR   RR   eee    eee  ff
 * CC      oo  oo rrr  r  aa aaa lll RRRRRR  ee   e ee   e ffff
 * CC    C oo  oo rr     aa  aaa lll RR  RR  eeeee  eeeee  ff
 *  CCCCC   oooo  rr      aaa aa lll RR   RR  eeeee  eeeee ff
 *
 * Copyright (c) 2021-2021. Ree-jp(https://ree-jp.net)
 */

namespace ree_jp\coral_reef\form;

use Exception;
use Frago9876543210\EasyForms\elements\Button;
use Frago9876543210\EasyForms\forms\Form;
use Frago9876543210\EasyForms\forms\MenuForm;
use Frago9876543210\EasyForms\forms\ModalForm;
use pocketmine\Player;
use pocketmine\Server;
use ree_jp\coral_reef\account\AccountManager;
use ree_jp\coral_reef\skill\SkillManager;
use ree_jp\coral_reef\sql\SQLManager;

class FormManager
{

    static function sendMenu(Player $p): void
    {
        $xuid = $p->getXuid();
        if (AccountManager::hasValue($xuid, 'form_cool_time')) return;
        AccountManager::setValue($xuid, 'form_cool_time', 10);

        $level = 'loading';
        $necessaryExperience = 'loading';
        try {
            $user = SQLManager::$manager->getUser($xuid);
            $level = $user->level;
            $necessaryExperience = $user->necessaryExperience;
        } catch (Exception $e) {
            Server::getInstance()->getLogger()->error('ユーザーデータの取得中に' . $e->getMessage());
        }

        if (AccountManager::hasValue($xuid, 'fly')) {
            $fly_status = '無効';
        } else {
            $fly_status = '有効';
        }
        $p->sendForm(new MenuForm('ReefServer Menu', "レベル : $level \nレベルアップまで : $necessaryExperience",
            [new Button('ワールド移動'), new Button("飛行を$fly_status にする"), new Button('ワープ地点'), new Button("スキル設定"), new Button('設定')],
            function (Player $p, Button $button): void {
                switch ($button->getValue()) {
                    case 0:
                        $p->sendForm(self::worldTeleportForm());
                        break;

                    case 1:
                        $xuid = $p->getXuid();
                        if (AccountManager::hasValue($xuid, 'fly')) {
                            AccountManager::setValue($xuid, 'fly', 0);
                            $p->setFlying(false);
                            $p->setAllowFlight(false);
                            $p->sendMessage('飛行を無効にしました');
                        } else {
                            if (in_array($p->getLevel()->getFolderName(), AccountManager::STOP_FLY_WORLD)) {
                                $p->sendMessage('このワールドで飛行することはできません');
                                return;
                            }
                            AccountManager::setValue($xuid, 'fly');
                            $p->setAllowFlight(true);
                            $p->setFlying(true);
                            $p->sendMessage('飛行を有効にしました');
                        }
                        break;

                    case 2:
                        $p->sendForm(MyWarpForm::myWarpForm($p));
                        break;

                    case 3:
                        $p->sendForm(self::skillSelectForm($p));
                        break;

                    case 9:
                        $p->sendForm(new MenuForm('Menu -> Setting'));
                        break;

                    default:
                        $p->sendMessage('ページを開けませんでした');
                }
            }));
    }

    static function worldTeleportForm(): MenuForm
    {
        return new MenuForm('Menu -> World', '移動するワールドを選択してください', [new Button('ロビー'), new Button('整地ワールド1')],
            function (Player $p, Button $button): void {
                switch ($button->getValue()) {
                    case 0:
                        AccountManager::teleport($p, 'lobby');
                        break;
                    case 1:
                        AccountManager::teleport($p, 'main_1');
                        break;
                    default:
                        $p->sendMessage('エラーが発生しました');
                }
            });
    }

    static function skillSelectForm(Player $p): Form
    {
        try {
            $user = SQLManager::$manager->getUser($p->getXuid());
        } catch (Exception $e) {
            Server::getInstance()->getLogger()->error('[SkillSelect]' . $p->getName() . 'の処理中に' . $e->getMessage());
            return FormManager::messageForm('エラーが発生しました');
        }
        $nowSkill = is_null($user->skill) ? 'なし' : $user->skill->name;
        $buttons = [new Button('スキルなし')];
        $skills = [null];
        foreach (SkillManager::SKILLS as $skillId) {
            $skill = SkillManager::getSkill($skillId);
            if (is_null($skill)) {
                array_push($buttons, new Button('エラーが発生しました'));
                array_push($skills, null);
            } else {
                array_push($buttons, new Button($skill->name));
                array_push($skills, $skill);
            }
        }
        return new MenuForm('Menu -> Skill', "現在のスキルは$nowSkill です", [
            new Button('なし'), new Button('ダブル'), new Button('トリプル'), new Button('てすと')],
            function (Player $p, Button $button) use ($skills): void {
                if (array_key_exists($button->getValue(), $skills)) {
                    $skill = $skills[$button->getValue()];
                    $skillName = is_null($skill) ? 'なし' : $skill->name;
                    $p->sendForm(new ModalForm('Skill -> Confirm', "スキルを$skillName に変更しますか?",
                        function (Player $p, bool $result) use ($skill): void {
                            if ($result) {
                                try {
                                    $user = SQLManager::$manager->getUser($p->getXuid());
                                    $user->skill = $skill;
                                    $p->sendMessage('スキルを変更しました');
                                } catch (Exception $e) {
                                    $p->sendMessage('エラーが発生しました');
                                    Server::getInstance()->getLogger()->error('[SkillConfirm]' . $p->getName() . 'の処理中に' . $e->getMessage());
                                }
                            } else $p->sendForm(self::skillSelectForm($p));
                        }));
                } else {
                    $p->sendMessage('エラーが発生しました');
                }
            });
    }


    static function messageForm(string $label): ModalForm
    {
        return new ModalForm('メッセージ', $label, function (): void {
        }, 'ok', 'ok');
    }
}
