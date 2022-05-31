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

namespace ree_jp\coral_reef\form\menu;

use bbo51dog\bboform\element\ClosureButton;
use bbo51dog\bboform\element\Dropdown;
use bbo51dog\bboform\element\Input;
use bbo51dog\bboform\form\ClosureCustomForm;
use bbo51dog\bboform\form\SimpleForm;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use ree_jp\coral_reef\account\AccountService;
use ree_jp\coral_reef\CoralReefPlugin;

class WorldTeleportForm
{
    static function sendForm(Player $p): void
    {
        $form = (new SimpleForm())
            ->setTitle("Menu -> World")
            ->setText("移動するワールドを選択してください")
            ->addElements(
                new ClosureButton("ロビー", null, function (Player $p) {
                    AccountService::teleport($p, "lobby");
                }),
                new ClosureButton("ショップ", null, function (Player $p) {
                    if (CoralReefPlugin::$plugin->isMain) {
                        AccountService::teleport($p, "shop");
                    } else {
                        $p->sendMessage("ショップは整地サーバー1のみです");
                    }
                }),
                new ClosureButton("整地ワールド1", null, function (Player $p) {
                    AccountService::teleport($p, "main_1");
                }),
                new ClosureButton("整地ワールド2", null, function (Player $p) {
                    AccountService::teleport($p, "main_2");
                }),
                new ClosureButton("テレポート", null, function (Player $p) {
                    self::sendTeleportForm($p);
                }),
                new ClosureButton("よくある質問", null, function (Player $p) {
                    if (CoralReefPlugin::$plugin->isMain) {
                        AccountService::teleport($p, "lobby", new Vector3(-7, 5, 328));
                    } else {
                        $p->sendMessage("よくある質問は整地サーバー1のみです");
                    }
                }),
            );
        $p->sendForm($form);
    }

    static function sendTeleportForm(Player $p): void
    {
        $worlds = ["整地ワールド1", "整地ワールド2"];
        $worldSelect = new Dropdown("移動したいワールドを選択してください", $worlds);
        $x = new Input("移動したいX座標を入力してください", "数字");
        $y = new Input("移動したいY座標を入力してください(埋まらない位置を指定してください)", "数字");
        $z = new Input("移動したいZ座標を入力してください", "数字");
        $form = new ClosureCustomForm(function () use ($p, $x, $y, $z, $worldSelect, $worlds): void {
            if (!isset($worlds[$worldSelect->getValue()])) {
                $p->sendMessage("エラーが発生しました");
                return;
            }
            $world = match ($worldSelect->getValue()) {
                0 => "main_1",
                1 => "main_2",
                default => ""
            };
            if (mb_strlen($x->getValue()) > 6 || mb_strlen($y->getValue()) > 3 || mb_strlen($z->getValue()) > 6) {
                $p->sendMessage("座標が大きすぎます");
                return;
            }
            $p->sendMessage("テレポートしています...");
            AccountService::teleport($p, $world, new Vector3($x->getValue(), $y->getValue(), $z->getValue()));
        });
        $form->setTitle("Menu -> Teleport");
        $form->addElements($worldSelect, $x, $y, $z);
        $p->sendForm($form);
    }
}