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

use bbo51dog\bboform\element\Button;
use bbo51dog\bboform\element\ButtonImage;
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
                ->setTitle("[dynamic_seichi_menu]")
                ->setText("§7現在のレベル §8: §f$level \n§7レベルアップまで §8: §f$necessaryExperience §7経験値\n§7経験値 §8: §f$exp\n§7所持金 §8: §f" . MoneyService::moneyFormat($money))
                ->addElements(
                    new ClosureButton(
                        "[grid_panel]§fストレージ", new ButtonImage(ButtonImage::TYPE_PATH, "textures/items/chest.png"),
                        function (Player $p) {
                            Server::getInstance()->dispatchCommand($p, 'stackstorage');
                            $p->sendMessage(TextFormat::DARK_GRAY . "ストレージを開いています(数秒かかることがあります)");
                        }
                    ),
                    new ClosureButton(
                        "[grid_panel]§7ワープ地点", new ButtonImage(ButtonImage::TYPE_PATH, "textures/items/compass_item.png"),
                        function (Player $p) use ($pool) {
                            MyWarpForm::sendForm($pool, $p);
                        }),
                    new ClosureButton(
                        "[grid_panel]§7ワールド移動", new ButtonImage(ButtonImage::TYPE_URL, "https://cdn-icons-png.flaticon.com/512/2072/2072130.png"),
                        function (Player $p) {
                            WorldTeleportForm::sendForm($p);
                        }),
                    new ClosureButton(
                        "[grid_panel]§7スキル設定", new ButtonImage(ButtonImage::TYPE_PATH, "textures/items/nether_star.png"),
                        function (Player $p) use ($store) {
                            SkillSettingForm::sendForm($store, $p);
                        }),
                    new ClosureButton(
                        "[grid_panel]§7クエスト", new ButtonImage(ButtonImage::TYPE_PATH, "textures/items/apple.png"),
                        function (Player $p) {
                            QuestForm::sendForm($p);
                        }),
                    new ClosureButton(
                        "[grid_panel]§7ランダムワープ", new ButtonImage(ButtonImage::TYPE_PATH, "textures/items/apple.png"),
                        function (Player $p) use ($pool, $store) {
                            self::sendRandomWarpForm($pool, $store, $p);
                        }),
                    new ClosureButton(
                        "[grid_panel]§7ゴミ箱", new ButtonImage(ButtonImage::TYPE_PATH, "textures/items/apple.png"),
                        function (Player $p) {
                            Server::getInstance()->dispatchCommand($p, "trash");
                        }
                    ),
                    new ClosureButton(
                        "[grid_panel]§7土地保護", new ButtonImage(ButtonImage::TYPE_PATH, "textures/items/apple.png"),
                        function (Player $p) {
                            Server::getInstance()->dispatchCommand($p, "reef-form land");
                        }),
                    new ClosureButton(
                        "[grid_panel]§7ランキング", new ButtonImage(ButtonImage::TYPE_PATH, "textures/items/apple.png"),
                        function (Player $p) use ($pool, $store) {
                            RankingForm::sendForm($pool, $store, $p);
                        }),
                    new ClosureButton(
                        "[grid_panel]§7ガチャ", new ButtonImage(ButtonImage::TYPE_PATH, "textures/items/apple.png"),
                        function (Player $p) use ($sqlRepo) {
                            GatyaForm::sendForm($sqlRepo, $p);
                        }),
                    new ClosureButton(
                        "[grid_panel]§7ギフト", new ButtonImage(ButtonImage::TYPE_PATH, "textures/items/apple.png"),
                        function (Player $p) use ($sqlRepo, $store) {
                            GiftForm::sendForm($sqlRepo, $store, $p);
                        }),
                    new ClosureButton(
                        "[grid_panel]§7ボーナスコード", new ButtonImage(ButtonImage::TYPE_PATH, "textures/items/apple.png"),
                        function (Player $p) use ($sqlRepo) {
                            BonusCodeForm::sendForm($sqlRepo, $p);
                        }),
                    new ClosureButton(
                        "[grid_panel]§7サーバー移動", new ButtonImage(ButtonImage::TYPE_PATH, "textures/items/apple.png"),
                        function (Player $p) {
                            Server::getInstance()->dispatchCommand($p, "exe-p server-select");
                        }),
                    new ClosureButton(
                        "[grid_panel]§7設定", new ButtonImage(ButtonImage::TYPE_PATH, "textures/items/apple.png"),
                        function (Player $p) use ($sqlRepo) {
                            SettingForm::sendForm($sqlRepo, $p);
                        }),
                    new ClosureButton(
                        "[grid_panel]§7アカウント設定", new ButtonImage(ButtonImage::TYPE_PATH, "textures/items/apple.png"),
                        function (Player $p) {
                            Server::getInstance()->dispatchCommand($p, "exe-p setting");
                        }),
                    new ClosureButton("[grid_panel]§7解説", new ButtonImage(ButtonImage::TYPE_PATH, "textures/items/apple.png"),
                        function () use ($p): void {
                            DocsForm::sendForm($p);
                        }),
                    new Button("[close]"),
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
