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

namespace ree_jp\coral_reef\form\land;

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
        $repo->getRecentSession($land->xuid, function (array $rows) use ($landStore, $repo, $land, $accountStore, $p): void {
            if (!$p->isOnline()) return;
            $session = array_shift($rows);
            $logoutIntervalDay = "不明";
            if (!empty($session)) {
                $logoutIntervalDay = floor((time() - strtotime($session["join_time"])) / (60 * 60 * 24)) . "日前";
            }

            $ownerName = $accountStore->getUserName($land->xuid);
            $aabb = $land->aabb;
            $space = (($aabb->maxX - $aabb->minX) + 1) * (($aabb->maxZ - $aabb->minZ) + 1);
            $form = (new SimpleForm())
                ->setTitle("Land -> Details")
                ->setText("土地保護の名前: $land->name\n所有者: $ownerName(直近ログイン$logoutIntervalDay)\nワールド: $land->level\n" .
                    "X座標: $aabb->minX - $aabb->maxX\nZ座標: $aabb->minZ - $aabb->maxZ\n大きさ: $space ブロック");

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
                            self::sendLandDeleteConfirmForm($repo, $accountStore, $landStore, $p, $land, TextFormat::RED . "本当に削除しますか?");
                        }
                    )
                );
            } else {
                $form->addElement(new ClosureButton("土地を削除する\n所有者が30日間ログインしていない場合、土地を奪うことができます", null,
                    function () use ($p, $accountStore, $landStore, $repo, $land) {
                        self::sendLandTakeForm($repo, $accountStore, $landStore, $p, $land);
                    }
                ));
            }
            $p->sendForm($form);
        }, null);
    }

    private static function sendLandDeleteConfirmForm(SQLRepository $repo, AccountStore $accountStore, LandStore $landStore, Player $p, LandData $land,
                                                      string        $confirmMessage): void
    {
        $form = new ModalForm(
            new ClosureButton(
                "はい", null,
                function (Player $p) use ($landStore, $repo, $land) {
                    LandService::deleteLand($repo, $landStore, $land, $p);
                },
            ),
            new ClosureButton(
                "いいえ", null,
                function (Player $p) use ($accountStore, $landStore, $repo, $land) {
                    self::sendForm($repo, $accountStore, $landStore, $land, $p);
                },
            ),
        );
        $form->setTitle("LandDelete -> Confirm")->setText($confirmMessage);
        $p->sendForm($form);
    }

    private static function sendLandTakeForm(SQLRepository $repo, AccountStore $accountStore, LandStore $landStore, Player $p, LandData $land): void
    {
        $user = $accountStore->getUser($p->getXuid());
        if ($user->level < 30) {
            $p->sendMessage("30レベル未満は土地を奪うことができません");
            return;
        }
        $repo->getRecentSession($land->xuid, function (array $rows) use ($repo, $accountStore, $landStore, $land, $p): void {
            if (!$p->isOnline()) return;
            $session = array_shift($rows);
            $logoutIntervalDay = 99999;
            if (!empty($session)) {
                $logoutIntervalDay = floor((time() - strtotime($session["join_time"])) / (60 * 60 * 24));
            }
            if ($logoutIntervalDay >= 30) {
                self::sendLandDeleteConfirmForm($repo, $accountStore, $landStore, $p, $land, "ランクが30以上かつ、この土地の所有者が30日以上ログインしていないため" .
                    "この土地の所有者に変わって土地保護を削除することができます\n" . TextFormat::RED . "自分でその土地を使わない場合はむやみやたらにこの機能を使用しないでください\n" .
                    "本当に削除しますか?");
            } else {
                $remainder = 30 - $logoutIntervalDay;
                $p->sendMessage("この土地の所有者の直近ログインは$logoutIntervalDay 日前です\n土地が奪えるようになるまであと$remainder 日です");
            }
        }, null);
    }
}