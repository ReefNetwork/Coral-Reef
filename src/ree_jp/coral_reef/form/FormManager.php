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

use Frago9876543210\EasyForms\elements\Button;
use Frago9876543210\EasyForms\forms\MenuForm;
use Frago9876543210\EasyForms\forms\ModalForm;
use pocketmine\math\Vector3;
use pocketmine\Player;
use pocketmine\Server;
use ree_jp\coral_reef\account\AccountManager;
use ree_jp\coral_reef\sql\SQLManager;

class FormManager
{

    static function sendMenu(Player $p): void
    {
        $xuid = $p->getXuid();
        if (AccountManager::hasValue($xuid, 'form_cool_time')) return;
        AccountManager::setValue($xuid, 'form_cool_time', 10);

        $user = SQLManager::$manager->getUser($xuid);
        $level = is_null($user) ? 'error' : $user->level;
        $necessaryExperience = is_null($user) ? 'error' : $user->necessaryExperience;
        $exp = is_null($user) ? 'error' : $user->experience;

        if (AccountManager::hasValue($xuid, 'fly')) {
            $fly_status = '無効';
        } else {
            $fly_status = '有効';
        }
        $p->sendForm(new MenuForm('ReefServer Menu', "レベル : $level\nレベルアップまで : $necessaryExperience\n経験値 : $exp",
            [new Button('ストレージ'), new Button("飛行を$fly_status にする"), new Button('ワープ地点'), new Button('ワールド移動'),
                new Button('サーバー移動'), new Button('スキル設定'), new Button('パーティー'), new Button('土地保護'),
                new Button("クエスト"), new Button('ガチャ'), new Button('ランダムワープ'), new Button('設定')],
            function (Player $p, Button $button): void {
                $xuid = $p->getXuid();
                switch ($button->getValue()) {
                    case 0:
                        Server::getInstance()->dispatchCommand($p, 'stackstorage');
                        break;
                    case 1:
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
                        MyWarpForm::sendWarpForm($p);
                        break;
                    case 3:
                        $p->sendForm(self::worldTeleportForm());
                        break;
                    case 4:
                        ServerSelectForm::sendServerSelectForm($p);
                        break;
                    case 5:
                        $p->sendForm(SkillSelectForm::SkillSelectForm($xuid));
                        break;
                    case 6:
                        $p->sendForm(PartyForm::partyForm($xuid));
                        break;
                    case 7:
                        $p->sendForm(LandForm::landForm($xuid));
                        break;
                    case 8:
                        $p->sendForm(QuestForm::questForm($p));
                        break;
                    case 9:
                        GatyaForm::sendGatyaForm($p);
                        break;
                    case 10:
                        $p->sendForm(new ModalForm('Menu -> RandomWarp', "ランダムな場所にワープしますか?\n
                            ※同じ場所にもう一度ランダムワープすることはできません.ランダムワープ後はワープ地点を設定することをおすすめします",
                            function (Player $p, bool $result): void {
                                if ($result) {
                                    $p->sendMessage('ランダムな場所にワープしています');
                                    $p->teleport(new Vector3(mt_rand(-10000, 10000), 100, mt_rand(-10000, 10000)));
                                } else FormManager::sendMenu($p);
                            }));
                        break;
                    case 11:
                        $p->sendForm(SettingForm::settingForm());
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


    static function messageForm(string $label): ModalForm
    {
        return new ModalForm('メッセージ', $label, function (): void {
        }, 'ok', 'ok');
    }
}
