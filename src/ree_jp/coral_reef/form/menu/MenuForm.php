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

namespace ree_jp\coral_reef\form\menu;

use bbo51dog\bboform\element\ClosureButton;
use bbo51dog\bboform\form\ModalForm;
use bbo51dog\bboform\form\SimpleForm;
use pocketmine\block\BlockLegacyIds;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use RankingForm;
use ree_jp\coral_reef\account\AccountService;
use ree_jp\coral_reef\account\AccountStore;
use ree_jp\coral_reef\CoralReefPlugin;
use ree_jp\coral_reef\form\gatya\GatyaForm;
use ree_jp\coral_reef\form\ranking\RankingForm;
use ree_jp\coral_reef\form\skill\SkillSettingForm;
use ree_jp\coral_reef\money\MoneyService;
use ree_jp\coral_reef\quest\QuestListener;
use ree_jp\coral_reef\sql\SQLRepository;

class MenuForm
{
    static function sendMenu(SQLRepository $repo, AccountStore $store, Player $p): void
    {
        $xuid = $p->getXuid();
        if ($store->hasValue($xuid, 'form_cool_time')) return;
        $store->setValue($xuid, 'form_cool_time', 10);

        MoneyService::getMoney($repo, $xuid, function (int $money) use ($store, $repo, $xuid, $p) {
            $user = $store->getUser($xuid);
            $level = is_null($user) ? 'error' : $user->level;
            $necessaryExperience = is_null($user) ? 'error' : $user->necessaryExperience;
            $exp = is_null($user) ? 'error' : $user->experience;

            $form = (new SimpleForm())
                ->setTitle("ReefServer Menu")
                ->setText("レベル : $level\nレベルアップまで : $necessaryExperience\n経験値 : $exp\nお金 : $money")
                ->addElements(
                    new ClosureButton(
                        "ストレージ", null,
                        function (Player $p) {
                            Server::getInstance()->dispatchCommand($p, 'stackstorage');
                            $p->sendMessage(TextFormat::DARK_GRAY . "ストレージを開いています(数秒かかることがあります)");
                        }
                    ),
                    new ClosureButton(
                        "ワープ地点", null, function (Player $p) use ($repo) {
                        MyWarpForm::sendForm($repo, $p);
                    }),
                    new ClosureButton(
                        "ワールド移動", null, function (Player $p) {
                        self::sendWorldTeleportForm($p);
                    }),
                    new ClosureButton(
                        "スキル設定", null, function (Player $p) use ($store) {
                        SkillSettingForm::sendForm($store, $p);
                    }),
                    new ClosureButton(
                        "クエスト", null, function (Player $p) {
                        QuestForm::sendForm($p);
                    }),
                    new ClosureButton(
                        "ランダムワープ", null, function (Player $p) use ($repo, $store) {
                        self::sendRandomWarpForm($repo, $store, $p);
                    }),
                    new ClosureButton(
                        "ゴミ箱", null,
                        function (Player $p) {
                            Server::getInstance()->dispatchCommand($p, "trash");
                        }
                    ),
                    new ClosureButton(
                        "土地保護", null, function (Player $p) {
                        Server::getInstance()->dispatchCommand($p, "reef-form land");
                    }),
                    new ClosureButton(
                        "ランキング", null, function (Player $p) use ($store, $repo) {
                        RankingForm::sendForm($repo, $store, $p);
                    }),
                    new ClosureButton(
                        "ガチャ", null, function (Player $p) use ($repo) {
                        GatyaForm::sendForm($repo, $p);
                    }),
                    new ClosureButton(
                        "ギフト", null, function (Player $p) use ($repo, $store) {
                        GiftForm::sendForm($repo, $store, $p);
                    }),
                    new ClosureButton(
                        "ボーナスコード", null, function (Player $p) use ($repo) {
                        BonusCodeForm::sendForm($repo, $p);
                    }),
                    new ClosureButton(
                        "サーバー移動", null, function (Player $p) use ($store, $repo) {
                        ServerSelectForm::sendForm($repo, $store, $p);
                    }),
                    new ClosureButton(
                        "設定", null, function (Player $p) use ($repo) {
                        SettingForm::sendForm($repo, $p);
                    }),
                );
            $p->sendForm($form);
        });
    }

    static function sendWorldTeleportForm(Player $p): void
    {
        $form = (new SimpleForm())
            ->setTitle("Menu -> World")
            ->setText("移動するワールドを選択してください")
            ->addElements(
                new ClosureButton(
                    "ロビー", null, function (Player $p) {
                    AccountService::teleport($p, "lobby");
                }),
                new ClosureButton(
                    "ショップ", null, function (Player $p) {
                    if (CoralReefPlugin::$plugin->isMain) {
                        AccountService::teleport($p, "lobby", new Vector3(512, 5, 512));
                    } else {
                        $p->sendMessage("ショップは整地サーバー1のみです");
                    }
                }),
                new ClosureButton(
                    "整地ワールド1", null, function (Player $p) {
                    AccountService::teleport($p, "main_1");
                }),
                new ClosureButton(
                    "整地ワールド2", null, function (Player $p) {
                    AccountService::teleport($p, "main_2");
                }),
            );
        $p->sendForm($form);
    }

    private static function sendRandomWarpForm(SQLRepository $repo, AccountStore $store, Player $p): void
    {
        $form = new ModalForm(
            new ClosureButton(
                "はい", null,
                function (Player $p) use ($store) {
                    if ($p->getWorld()->getFolderName() === "lobby") {
                        $p->sendMessage("このワールドでは使用することが出来ません");
                        return;
                    }
                    if ($store->hasValue($p->getXuid(), "random_warp_cool_time")) { // 30秒のクールタイム
                        $p->sendMessage("連続で使用するには30秒お待ちください");
                        return;
                    }
                    $store->setValue($p->getXuid(), "random_warp_cool_time", 20 * 30);
                    $p->sendMessage("ランダムな場所にワープしています\nワールドの読み込みに時間がかかる場合があります");

                    $vec = new Vector3(mt_rand(-10000, 10000), 100, mt_rand(-10000, 10000));
                    if ($p->getWorld()->getBlock($vec)->getId() === BlockLegacyIds::AIR) { // 地面にワープ出来るように調整
                        while ($p->getWorld()->getBlock($vec)->getId() !== BlockLegacyIds::AIR && $vec->y > 0) {
                            $vec = $vec->subtract(0, 1, 0);
                        }
                        $vec = $vec->add(0, 1, 0);
                    } else {
                        while ($p->getWorld()->getBlock($vec)->getId() === BlockLegacyIds::AIR && $vec->y < 300) {
                            $vec = $vec->add(0, 1, 0);
                        }
                    }
                    $p->teleport($vec);
                    QuestListener::callSubscribedQuest($p->getXuid(), QuestListener::RANDOM_WARP, $vec);
                }
            ),
            new ClosureButton(
                "いいえ", null, function (Player $p) use ($store, $repo) {
                MenuForm::sendMenu($repo, $store, $p);
            })
        );
        $form->setTitle("RandomWarp")
            ->setText("※同じ場所にもう一度ランダムワープすることはできません。ランダムワープ後はワープ地点を設定することをおすすめします。");
        $p->sendForm($form);
    }
}
