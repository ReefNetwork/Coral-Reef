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

namespace ree_jp\coral_reef\form\command;

use bbo51dog\bboform\element\ClosureButton;
use bbo51dog\bboform\element\Input;
use bbo51dog\bboform\element\Label;
use bbo51dog\bboform\form\ClosureCustomForm;
use bbo51dog\bboform\form\ModalForm;
use bbo51dog\bboform\form\SimpleForm;
use pocketmine\player\Player;
use pocketmine\Server;
use ree_jp\coral_reef\account\AccountStore;
use ree_jp\coral_reef\account\UserAccount;
use ree_jp\coral_reef\land\LandData;
use ree_jp\coral_reef\land\LandService;
use ree_jp\coral_reef\land\LandStore;
use ree_jp\coral_reef\sql\SQLRepository;

class ReefAdminForm
{
    static function sendForm(SQLRepository $repo, AccountStore $accountStore, LandStore $landStore, Player $p)
    {
        $form = (new SimpleForm())
            ->setTitle("Admin")
            ->setText("OP用サーバー管理ツール");
        foreach (Server::getInstance()->getOnlinePlayers() as $onlinePlayer) {
            if (!$onlinePlayer instanceof Player) return;
            $form->addElement(new ClosureButton(
                $onlinePlayer->getName(), null,
                function (Player $p) use ($landStore, $accountStore, $repo, $onlinePlayer) {
                    self::sendUserAdminForm($repo, $accountStore, $landStore, $p, $accountStore->getUser($onlinePlayer->getXuid()));
                }
            ));
        }
        $p->sendForm($form);
    }

    private static function sendUserAdminForm(SQLRepository $repo, AccountStore $accountStore, LandStore $landStore, Player $p, UserAccount $user)
    {
        $p->sendForm((new SimpleForm())
            ->setTitle("Admin -> User")
            ->setText($user->name . " さんの管理画面です")
            ->addElements(
                new ClosureButton(
                    "経験値", null, function (Player $p) use ($user) {
                    self::sendExpAdminForm($p, $user);
                }),
                new ClosureButton(
                    "土地保護", null, function (Player $p) use ($landStore, $accountStore, $repo, $user) {
                    self::sendLandAdminForm($repo, $accountStore, $landStore, $p, $user);
                }),
            ));
    }

    private static function sendExpAdminForm(Player $p, UserAccount $user)
    {
        $expInput = new Input("経験値");
        $form = (new ClosureCustomForm(function (Player $p) use ($user, $expInput) {
            /** @var int|false $exp */
            $exp = filter_var($expInput->getValue(), FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
            if ($exp === false) {
                $p->sendMessage("無効な値です");
                return;
            }
            $user->setXp($exp);
            $p->sendMessage($user->name . "さんの経験値を" . $exp . "に設定しました");
            $target = Server::getInstance()->getPlayerByPrefix($user->name);
            if ($target instanceof Player) {
                $target->sendMessage("経験値が" . $p->getName() . "さんによって" . $exp . "に設定されました");
            }
        }))
            ->setTitle("Admin -> Exp")
            ->addElements(
                new Label($user->name . "さんの経験値を設定\n現在の経験値: " . $user->experience),
                $expInput
            );
        $p->sendForm($form);
    }

    private static function sendLandAdminForm(SQLRepository $repo, AccountStore $accountStore, LandStore $landStore, Player $p, UserAccount $user)
    {
        $form = (new SimpleForm())
            ->setTitle("Admin -> Land")
            ->setText($user->name . "さんの土地一覧");

        foreach (LandService::getMyLand($landStore, $user->xuid) as $land) {
            $form->addElement(new ClosureButton(
                $land->name, null, function (Player $p) use ($accountStore, $landStore, $repo, $land) {
                self::sendLandAdminDetailForm($repo, $accountStore, $landStore, $p, $land);
            }));
        }
        $p->sendForm($form);
    }

    private static function sendLandAdminDetailForm(SQLRepository $repo, AccountStore $accountStore, LandStore $landStore, Player $p, LandData $land)
    {
        $ownerName = $accountStore->getUserName($land->xuid);
        $aabb = $land->aabb;
        $space = (($aabb->maxX - $aabb->minX) + 1) * (($aabb->maxZ - $aabb->minZ) + 1);
        $form = (new SimpleForm())
            ->setTitle("Admin -> Land -> Detail")
            ->setText("土地保護の名前: $land->name\nワールド: $land->level\nX座標: $aabb->minX - $aabb->maxX\nZ座標: $aabb->minZ - $aabb->maxZ" .
                "\n大きさ: $space ブロック")
            ->addElement(new ClosureButton(
                "土地を削除",
                null,
                function (Player $p) use ($landStore, $repo, $accountStore, $ownerName, $land) {
                    $p->sendForm((new ModalForm(
                        new ClosureButton(
                            "はい", null, function (Player $p) use ($landStore, $repo, $land) {
                            LandService::deleteLand($repo, $landStore, $land, $p);
                        }),
                        new ClosureButton(
                            "いいえ", null, function (Player $p) use ($landStore, $accountStore, $repo, $land) {
                            self::sendLandAdminDetailForm($repo, $accountStore, $landStore, $p, $land);
                        })
                    ))
                        ->setTitle("Admin -> Land -> Delete")
                        ->setText($ownerName . "さんの土地「" . $land->name . "」を削除しますか？")
                    );
                }
            ));
        $p->sendForm($form);
    }
}
