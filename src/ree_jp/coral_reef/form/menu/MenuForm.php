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
use ree_jp\coral_reef\account\AccountStore;
use ree_jp\coral_reef\form\gatya\GatyaForm;
use ree_jp\coral_reef\form\item\DocsForm;
use ree_jp\coral_reef\form\ranking\RankingForm;
use ree_jp\coral_reef\form\skill\SkillSettingForm;
use ree_jp\coral_reef\money\MoneyService;
use ree_jp\coral_reef\quest\QuestListener;
use ree_jp\coral_reef\sql\mysql\SQLRepository;
use ree_jp\coral_reef\sql\RepositoryPool;

class MenuForm
{
    static function sendMenu(RepositoryPool $pool, AccountStore $store, Player $p): void
    {
        $xuid = $p->getXuid();
        if ($store->hasValue($xuid, 'form_cool_time')) return;
        $store->setValue($xuid, 'form_cool_time', 10);

        /** @var $sqlRepo SQLRepository */
        $sqlRepo = $pool->get(SQLRepository::class);

        MoneyService::getMoney($sqlRepo, $xuid, function (int $money) use ($pool, $store, $sqlRepo, $xuid, $p) {
            if (!$p->isOnline()) return;

            $user = $store->getUser($xuid);
            $level = is_null($user) ? 'error' : $user->level;
            $necessaryExperience = is_null($user) ? 'error' : $user->necessaryExperience;
            $exp = is_null($user) ? 'error' : $user->experience;

            $form = (new SimpleForm())
                ->setTitle(TextFormat::GREEN . "Reef" . TextFormat::WHITE . " Menu")
                ->setText("§7現在のレベル §8: §f$level \n§7レベルアップまで §8: §f$necessaryExperience §7経験値\n§7経験値 §8: §f$exp\n§7所持金 §8: §f$money")
                ->addElements(
                    new ClosureButton(
                        "ストレージ \n§7StackStorageを開きます", null,
                        function (Player $p) {
                            Server::getInstance()->dispatchCommand($p, 'stackstorage');
                            $p->sendMessage(TextFormat::DARK_GRAY . "ストレージを開いています(数秒かかることがあります)");
                        }
                    ),
                    new ClosureButton(
                        "ワープ地点 \n§7自分だけのワープ地点を作成します", null, function (Player $p) use ($pool) {
                        MyWarpForm::sendForm($pool, $p);
                    }),
                    new ClosureButton(
                        "ワールド移動 \n§7ロビーやショップに移動ができます", null, function (Player $p) {
                        WorldTeleportForm::sendForm($p);
                    }),
                    new ClosureButton(
                        "スキル設定 \n§7掘った時に発動するスキルを設定できます", null, function (Player $p) use ($store) {
                        SkillSettingForm::sendForm($store, $p);
                    }),
                    new ClosureButton(
                        "クエスト \n§7チュートリアルなどがあります", null, function (Player $p) {
                        QuestForm::sendForm($p);
                    }),
                    new ClosureButton(
                        "ランダムワープ \n§7ランダムな場所にワープします", null, function (Player $p) use ($pool, $store) {
                        self::sendRandomWarpForm($pool, $store, $p);
                    }),
                    new ClosureButton(
                        "ゴミ箱 \n§7アイテムを捨てる事ができます", null,
                        function (Player $p) {
                            Server::getInstance()->dispatchCommand($p, "trash");
                        }
                    ),
                    new ClosureButton(
                        "土地保護 \n§7土地を編集したり作成ができます", null, function (Player $p) {
                        Server::getInstance()->dispatchCommand($p, "reef-form land");
                    }),
                    new ClosureButton(
                        "ランキング \n§7所持金ランキングなどが見れます", null, function (Player $p) use ($pool, $store) {
                        RankingForm::sendForm($pool, $store, $p);
                    }),
                    new ClosureButton(
                        "ガチャ \n§7ガチャチケットでひくことができます", null, function (Player $p) use ($sqlRepo) {
                        GatyaForm::sendForm($sqlRepo, $p);
                    }),
                    new ClosureButton(
                        "ギフト \n§7ギフトがある場合はここから受け取れます", null, function (Player $p) use ($sqlRepo, $store) {
                        GiftForm::sendForm($sqlRepo, $store, $p);
                    }),
                    new ClosureButton(
                        "ボーナスコード \n§7運営が配布するコードで特別なアイテムが受け取れます", null, function (Player $p) use ($sqlRepo) {
                        BonusCodeForm::sendForm($sqlRepo, $p);
                    }),
                    new ClosureButton(
                        "サーバー移動 \n§7整地2などへの移動ができます", null, function (Player $p) {
                        Server::getInstance()->dispatchCommand($p, "exe-p server-select");
                    }),
                    new ClosureButton(
                        "設定", null, function (Player $p) use ($sqlRepo) {
                        SettingForm::sendForm($sqlRepo, $p);
                    }),
                    new ClosureButton(
                        "アカウント設定 \n§7称号の変更や自分の情報を確認できます", null, function (Player $p) {
                        Server::getInstance()->dispatchCommand($p, "exe-p setting");
                    }),
                    new ClosureButton("解説 \n§7機能の解説やルールの確認ができます", null, function () use ($p): void {
                        DocsForm::sendForm($p);
                    }),
                );
            $p->sendForm($form);
        });
    }

    private static function sendRandomWarpForm(RepositoryPool $pool, AccountStore $store, Player $p): void
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
                "いいえ", null, function (Player $p) use ($pool, $store) {
                MenuForm::sendMenu($pool, $store, $p);
            })
        );
        $form->setTitle("RandomWarp")
            ->setText("※同じ場所にもう一度ランダムワープすることはできません。ランダムワープ後はワープ地点を設定することをおすすめします。");
        $p->sendForm($form);
    }
}
