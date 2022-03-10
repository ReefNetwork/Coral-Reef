<?php
/*
 *  CCCCC                        lll RRRRRR                 fff
 * CC    C  oooo  rr rr    aa aa lll RR   RR   eee    eee  ff
 * CC      oo  oo rrr  r  aa aaa lll RRRRRR  ee   e ee   e ffff
 * CC    C oo  oo rr     aa  aaa lll RR  RR  eeeee  eeeee  ff
 *  CCCCC   oooo  rr      aaa aa lll RR   RR  eeeee  eeeee ff
 *
 * Copyright (c) 2022. Ree-jp(https://ree-jp.net)
 */

namespace ree_jp\coral_reef\form\command\land;

use bbo51dog\bboform\element\ClosureButton;
use bbo51dog\bboform\form\ModalForm;
use bbo51dog\bboform\form\SimpleForm;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use ree_jp\coral_reef\account\AccountStore;
use ree_jp\coral_reef\land\LandData;
use ree_jp\coral_reef\land\LandService;
use ree_jp\coral_reef\land\LandStore;
use ree_jp\coral_reef\sql\SQLRepository;

class LandDetailForm
{
    static function sendForm(SQLRepository $repo, AccountStore $accountStore, LandStore $landStore, LandData $land, Player $p): void
    {
        $ownerName = $accountStore->getUserName($land->xuid);
        $aabb = $land->aabb;
        $space = (($aabb->maxX - $aabb->minX) + 1) * (($aabb->maxZ - $aabb->minZ) + 1);
        $form = (new SimpleForm())
            ->setTitle("Land -> Edit")
            ->setText("土地保護の名前: $land->name\n所有者: $ownerName\nワールド: $land->level\nX座標: $aabb->minX - $aabb->maxX\nZ座標: $aabb->minZ - $aabb->maxZ\n" .
                "大きさ: $space ブロック");

        if ($land->xuid === $p->getXuid()) {
            $form->addElements(
                new ClosureButton(
                    "土地保護を共有する", null,
                    function (Player $p) use ($accountStore, $repo, $land) {
                        LandShareForm::sendForm($repo, $accountStore, $land, $p);
                    }
                ),
                new ClosureButton(
                    "土地を削除する", null,
                    function (Player $p) use ($accountStore, $landStore, $repo, $land) {
                        self::sendLandDeleteConfirmForm($repo, $accountStore, $landStore, $p, $land);
                    }
                )
            );
        }
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
                    self::sendForm($repo, $accountStore, $store, $land, $p);
                },
            ),
        );
        $form->setText(TextFormat::RED . "本当に削除しますか?");
        $p->sendForm($form);
    }
}