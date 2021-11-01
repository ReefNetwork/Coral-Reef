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

use bbo51dog\bboform\element\Button;
use bbo51dog\bboform\element\ClosureButton;
use bbo51dog\bboform\form\ModalForm;
use bbo51dog\bboform\form\SimpleForm;
use pocketmine\block\BlockIds;
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
        $form = (new SimpleForm())
            ->setTitle("ReefServer Menu")
            ->setText("レベル : $level\nレベルアップまで : $necessaryExperience\n経験値 : $exp")
            ->addElements(
                new ClosureButton(
                    "ストレージ",
                    null,
                    function (Player $p, ClosureButton $button) {
                        Server::getInstance()->dispatchCommand($p, 'stackstorage');
                    }
                ),
                new ClosureButton(
                    "飛行を$fly_status にする",
                    null,
                    function (Player $p, ClosureButton $button) use ($xuid) {
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
                    }
                ),
                new ClosureButton(
                    "ワープ地点",
                    null,
                    function (Player $p, ClosureButton $button) {
                        MyWarpForm::sendWarpForm($p);
                    }
                ),
                new ClosureButton(
                    "ワールド移動",
                    null,
                    function (Player $p, ClosureButton $button) {
                        $p->sendForm(self::worldTeleportForm());
                    }
                ),
                new ClosureButton(
                    "サーバー移動",
                    null,
                    function (Player $p, ClosureButton $button) {
                        ServerSelectForm::sendServerSelectForm($p);
                    }
                ),
                new ClosureButton(
                    "スキル設定",
                    null,
                    function (Player $p, ClosureButton $button) use ($xuid) {
                        $p->sendForm(SkillSelectForm::SkillSelectForm($xuid));
                    }
                ),
                new ClosureButton(
                    "パーティー",
                    null,
                    function (Player $p, ClosureButton $button) use ($xuid) {
                        $p->sendForm(PartyForm::partyForm($xuid));
                    }
                ),
                new ClosureButton(
                    "土地保護",
                    null,
                    function (Player $p, ClosureButton $button) use ($xuid) {
                        $p->sendForm(LandForm::landForm($xuid));
                    }
                ),
                new ClosureButton(
                    "クエスト",
                    null,
                    function (Player $p, ClosureButton $button) {
                        $p->sendForm(QuestForm::questForm($p));
                    }
                ),
                new ClosureButton(
                    "ガチャ",
                    null,
                    function (Player $p, ClosureButton $button) {
                        GatyaForm::sendGatyaForm($p);
                    }
                ),
                new ClosureButton(
                    "ギフト",
                    null,
                    function (Player $p, ClosureButton $button) {
                        GiftForm::sendGiftForm($p);
                    }
                ),
                new ClosureButton(
                    "ランダムワープ",
                    null,
                    function (Player $p, ClosureButton $button) {
                        $p->sendForm(self::randomWarpModalForm());
                    }
                ),
                new ClosureButton(
                    "設定",
                    null,
                    function (Player $p, ClosureButton $button) {
                        $p->sendForm(SettingForm::settingForm());
                    }
                ),

            );
        $p->sendForm($form);
    }

    static function worldTeleportForm(): SimpleForm
    {
        return (new SimpleForm())
            ->setTitle("Menu -> World")
            ->setText("移動するワールドを選択してください")
            ->addElements(
                new ClosureButton(
                    "ロビー",
                    null,
                    function (Player $p, ClosureButton $button) {
                        AccountManager::teleport($p, 'lobby');
                    }
                ),
                new ClosureButton(
                    "整地ワールド1",
                    null,
                    function (Player $p, ClosureButton $button) {
                        AccountManager::teleport($p, 'main_1');
                    }
                ),
                new ClosureButton(
                    "整地ワールド2",
                    null,
                    function (Player $p, ClosureButton $button) {
                        AccountManager::teleport($p, 'main_2');
                    }
                ),
            );
    }


    static function messageForm(string $label): ModalForm
    {
        return (new ModalForm(
            new Button("ok"),
            new Button("ok")
        ))
            ->setTitle("メッセージ")
            ->setText($label);
    }

    private static function randomWarpModalForm(): ModalForm
    {
        return (new ModalForm(
            new ClosureButton(
                "はい",
                null,
                function (Player $p, ClosureButton $button) {
                    if (AccountManager::hasValue($p->getXuid(), "random_warp_cool_time")) { // 30秒のクールタイム
                        $p->sendMessage("連続で使用するには30秒お待ちください");
                        return;
                    }
                    AccountManager::setValue($p->getXuid(), "random_warp_cool_time", 20 * 30);
                    $p->sendMessage("ランダムな場所にワープしています\nワールドの読み込みに時間がかかる場合があります");
                    $vec = new Vector3(mt_rand(-10000, 10000), 100, mt_rand(-10000, 10000));
                    if ($p->getLevelNonNull()->getBlockIdAt($vec->x, $vec->y, $vec->z) === BlockIds::AIR) { // 地面にワープ出来るように調整
                        while ($p->getLevelNonNull()->getBlockIdAt($vec->x, $vec->y, $vec->z) !== BlockIds::AIR && $vec->y > 0) {
                            $vec = $vec->subtract(0, 1);
                        }
                        $vec = $vec->add(0, 1);
                    } else {
                        while ($p->getLevelNonNull()->getBlockIdAt($vec->x, $vec->y, $vec->z) === BlockIds::AIR && $vec->y < 300) {
                            $vec = $vec->add(0, 1);
                        }
                    }
                    $p->teleport($vec);
                }
            ),
            new ClosureButton(
                "いいえ",
                null,
                function (Player $p, ClosureButton $button) {
                    FormManager::sendMenu($p);
                }
            )
        ))
            ->setTitle("Menu -> RandomWarp")
            ->setText("※同じ場所にもう一度ランダムワープすることはできません。ランダムワープ後はワープ地点を設定することをおすすめします。");
    }
}
