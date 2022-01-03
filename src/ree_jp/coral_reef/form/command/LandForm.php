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

namespace ree_jp\coral_reef\form\command;

use bbo51dog\bboform\element\Button;
use bbo51dog\bboform\element\ClosureButton;
use bbo51dog\bboform\element\Input;
use bbo51dog\bboform\element\Label;
use bbo51dog\bboform\form\ClosureCustomForm;
use bbo51dog\bboform\form\ModalForm;
use bbo51dog\bboform\form\SimpleForm;
use pocketmine\item\VanillaItems;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use ree_jp\coral_reef\account\AccountService;
use ree_jp\coral_reef\account\AccountStore;
use ree_jp\coral_reef\land\LandData;
use ree_jp\coral_reef\land\LandService;
use ree_jp\coral_reef\land\LandStore;
use ree_jp\coral_reef\sql\SQLRepository;

class LandForm
{
    static function sendForm(SQLRepository $repo, AccountStore $accountStore, LandStore $landStore, Player $p): void
    {
        $form = (new SimpleForm())
            ->setTitle("Land")
            ->setText("土地を保護できます");
        $lands = LandService::getMyLand($landStore, $p->getXuid());
        foreach ($lands as $land) {
            if ($land instanceof LandData) {
                $button = new ClosureButton(
                    $land->name, null,
                    function (Player $p) use ($accountStore, $landStore, $repo, $land) {
                        self::sendLandEditForm($repo, $accountStore, $landStore, $p, $land);
                    }
                );
            } else {
                $button = new Button('エラーが発生しました');
            }
            $form->addElement($button);
        }
        $form->addElement(new ClosureButton(
            "パーティー", null,
            function (Player $p) use ($landStore, $accountStore) {
                PartyForm::sendForm($accountStore, $landStore, $p);
            }
        ));
        $form->addElement(new ClosureButton(
            "新しく土地保護を作成する", null,
            function (Player $p) {
                $p->getInventory()->addItem(VanillaItems::CLOCK()->setLore(['土地保護を設定します']));
                $p->sendMessage('時計を地面にクリックすることで土地保護を設定できます');
            }
        ));
        $p->sendForm($form);
    }

    static function sendLandEditForm(SQLRepository $repo, AccountStore $accountStore, LandStore $store, Player $p, LandData $land): void
    {
        $aabb = $land->aabb;
        $space = (($aabb->maxX - $aabb->minX) + 1) * (($aabb->maxZ - $aabb->minZ) + 1);
        $form = (new SimpleForm())
            ->setTitle("Land -> Edit")
            ->setText("土地保護の名前: $land->name\nワールド: $land->level\nX座標: $aabb->minX - $aabb->maxX\nZ座標: $aabb->minZ - $aabb->maxZ\n" .
                "大きさ: $space ブロック")
            ->addElements(
                new ClosureButton(
                    "土地保護を共有する", null,
                    function (Player $p) use ($accountStore, $store, $repo, $land) {
                        LandShareForm::sendForm($repo, $accountStore, $land, $p);
                    }
                ),
                new ClosureButton(
                    "土地を削除する", null,
                    function (Player $p) use ($accountStore, $store, $repo, $land) {
                        self::sendLandDeleteConfirmForm($repo, $accountStore, $store, $p, $land);
                    }
                )
            );
        $p->sendForm($form);
    }

    static function sendLandCreateAssistForm(SQLRepository $repo, AccountStore $accountStore, LandStore $store, Player $p, Vector3 $vec3): void
    {
        $xuid = $p->getXuid();
        $x1 = '設定されていません';
        $z1 = '設定されていません';
        if (isset($store->pos[$xuid][1]) && $store->pos[$xuid][1] instanceof Vector3) {
            $storeVec = $store->pos[$xuid][1];
            $x1 = $storeVec->getFloorX();
            $z1 = $storeVec->getFloorZ();
        }
        $x2 = '設定されていません';
        $z2 = '設定されていません';
        if (isset($store->pos[$xuid][2]) && $store->pos[$xuid][2] instanceof Vector3) {
            $storeVec = $store->pos[$xuid][2];
            $x2 = $storeVec->getFloorX();
            $z2 = $storeVec->getFloorZ();
        }
        $form = (new SimpleForm())
            ->setTitle("Land Create Assist")
            ->setText("クリックした場所に地点を設定して土地保護を作成できます\nシフト中に時計をクリックすると指定した範囲を確認することもできます")
            ->addElements(
                new ClosureButton(
                    "土地保護を作成する", null,
                    function (Player $p) use ($accountStore, $repo, $store, $x1, $z1, $x2, $z2) {
                        self::sendLandCreateForm($repo, $accountStore, $store, $p, $x1, $z1, $x2, $z2);
                    }
                ),
                new ClosureButton(
                    "地点1を設定する", null,
                    function (Player $p) use ($store, $vec3) {
                        $store->pos[$p->getXuid()][1] = $vec3;
                        $p->sendMessage("地点1 を設定しました");
                    }
                ),
                new ClosureButton(
                    "地点2を設定する", null,
                    function (Player $p) use ($store, $vec3) {
                        $store->pos[$p->getXuid()][2] = $vec3;
                        $p->sendMessage("地点2 を設定しました");
                    }
                ),
            );
        $p->sendForm($form);
    }

    static function sendLandCreateForm(SQLRepository $repo, AccountStore $accountStore, LandStore $store, Player $p, string $x1 = '', string $z1 = '', string $x2 = '', string $z2 = ''): void
    {
        $landNameInput = new Input('土地の名前', '土地1', '');
        $x1Input = new Input('x座標1', '1', $x1);
        $z1Input = new Input('z座標1', '1', $z1);
        $x2Input = new Input('x座標2', '10', $x2);
        $z2Input = new Input('z座標2', '10', $z2);
        $form = new ClosureCustomForm(
            function (Player $p) use ($accountStore, $repo, $store, $landNameInput, $x1Input, $z1Input, $x2Input, $z2Input) {
                if (!in_array($p->getWorld()->getFolderName(), LandService::CAN_CREATE_LAND) && !AccountService::isOp($p)) {
                    $p->sendMessage('このワールドでは土地保護が出来ません');
                    return;
                }
                if (is_numeric($x1Input->getValue()) && is_numeric($z1Input->getValue())
                    && is_numeric($x2Input->getValue()) && is_numeric($z2Input->getValue())) {
                    $x1 = intval($x1Input->getValue());
                    $z1 = intval($z1Input->getValue());
                    $x2 = intval($x2Input->getValue());
                    $z2 = intval($z2Input->getValue());
                    $name = $landNameInput->getValue();
                    if (mb_strlen($name) > 0) {
                        $aabb = LandService::getAabb($x1, $z1, $x2, $z2);
                        $land = new LandData($p->getXuid(), $name, $p->getWorld()->getFolderName(), $aabb);
                        $result = LandService::canCreateLand($store, $land);
                        $space = (($aabb->maxX - $aabb->minX) + 1) * (($aabb->maxZ - $aabb->minZ) + 1);

                        if ($space > 1000000) {
                            $p->sendMessage("デカすぎます100万ブロック以下にしてください");
                            return;
                        }
                        if ($space < 10) {
                            $p->sendMessage("小さすぎます10ブロック以上にしてください");
                            return;
                        }
                        if (is_null($result)) {
                            LandService::addLand($repo, $store, $land, $p);
                        } else {
                            $name = $accountStore->getUserName($land->xuid);
                            $p->sendMessage("指定した土地の一部が$name さんの$result->name とかぶっていたため土地を作成することが出来ませんでした");
                        }
                    } else $p->sendMessage('名前が短すぎます');
                } else $p->sendMessage('座標の欄には数字を入力してください');
            }
        );
        $form->setTitle("Land -> Create")->addElements(
            new Label("作成する土地の情報を入力してください"),
            $landNameInput, $x1Input, $z1Input, $x2Input, $z2Input,
        );
        $p->sendForm($form);
    }

    private static function sendLandDeleteConfirmForm(SQLRepository $repo, AccountStore $accountStore, LandStore $store, Player $p, LandData $land): void
    {
        $form = new ModalForm(
            new ClosureButton(
                "はい", null,
                function (Player $p) use ($store, $repo, $land) {
                    LandService::deleteLand($repo, $store, $land, $p);
                },
            ),
            new ClosureButton(
                "いいえ", null,
                function (Player $p) use ($accountStore, $store, $repo, $land) {
                    self::sendLandEditForm($repo, $accountStore, $store, $p, $land);
                },
            ),
        );
        $form->setText(TextFormat::RED . "本当に削除しますか?");
        $p->sendForm($form);
    }
}
