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

use bbo51dog\bboform\element\ClosureButton;
use bbo51dog\bboform\form\ModalForm;
use bbo51dog\bboform\form\SimpleForm;
use pocketmine\Player;
use ree_jp\coral_reef\gatya\NormalGatya;
use ree_jp\coral_reef\sql\SQLConst;
use ree_jp\coral_reef\sql\SQLManager;

class GatyaForm
{
    static function sendGatyaForm(Player $p): void
    {
        SQLManager::$manager->getAllSubtypeValue($p->getXuid(), SQLConst::TYPE_TICKETS, function (array $rows) use ($p) {
            $normal = 0;
            foreach ($rows as $row) {
                if ($row['subtype'] === SQLConst::TICKETS_NORMAL && isset($row['value'])) $normal = $row['value'];
            }
            $form = (new SimpleForm())
                ->setTitle("Menu -> Gatya")
                ->setText("ノーマルガチャチケット: $normal 個")
                ->addElements(
                    new ClosureButton(
                        "ノーマルガチャ",
                        null,
                        function (Player $p, ClosureButton $button) use ($normal) {
                            self::sendGatyaConfirmForm($p, 1, $normal);
                        }
                    ),
                    new ClosureButton(
                        "ノーマルガチャ 10連続",
                        null,
                        function (Player $p, ClosureButton $button) use ($normal) {
                            self::sendGatyaConfirmForm($p, 10, $normal);
                        }
                    ),
                );
            $p->sendForm($form);
        });
    }

    private static function sendGatyaConfirmForm(Player $p, int $num, int $tickets)
    {
        $after = $tickets - $num;
        $p->sendForm(
            (new ModalForm(
                new ClosureButton(
                    "はい",
                    null,
                    function (Player $p, ClosureButton $button) use ($num, $tickets) {
                        if ($tickets >= $num) {
                            NormalGatya::gatya($p, $num);
                        } else {
                            $p->sendMessage('チケットが足りません');
                        }
                    }
                ),
                new ClosureButton(
                    "いいえ",
                    null,
                    function (Player $p, ClosureButton $button) {
                        self::sendGatyaForm($p);
                    }
                )
            ))
                ->setTitle("NormalGatya -> Confirm")
                ->setText("ノーマルガチャチケットを10個消費してノーマルガチャを回しますか？\n$tickets -> $after")
        );
    }
}
