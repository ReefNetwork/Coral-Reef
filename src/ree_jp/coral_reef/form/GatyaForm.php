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
use bbo51dog\bboform\element\Slider;
use bbo51dog\bboform\form\ClosureCustomForm;
use bbo51dog\bboform\form\ModalForm;
use bbo51dog\bboform\form\SimpleForm;
use pocketmine\Player;
use ree_jp\coral_reef\gatya\event\Christmas2021;
use ree_jp\coral_reef\gatya\NormalGatya;
use ree_jp\coral_reef\sql\SQLConst;
use ree_jp\coral_reef\sql\SQLManager;

class GatyaForm
{
    static function sendGatyaForm(Player $p): void
    {
        SQLManager::$manager->getAllSubtypeValue($p->getXuid(), SQLConst::TYPE_TICKETS, function (array $rows) use ($p) {
            $normal = 0;
            $christmas = 0;
            foreach ($rows as $row) {
                if ($row['subtype'] === SQLConst::TICKETS_NORMAL) $normal = $row['value'];
                if ($row['subtype'] === SQLConst::TICKETS_CHRISTMAS_2021) $christmas = $row['value'];
            }
            $form = (new SimpleForm())
                ->setTitle("Menu -> Gatya")
                ->setText("ノーマルガチャチケット: $normal 個\nクリスマスガチャチケット: $christmas 個")
                ->addElements(
                    new ClosureButton(
                        "ノーマルガチャ",
                        null,
                        function (Player $p, ClosureButton $button) use ($normal) {
                            self::sendGatyaNumberChoices($p, SQLConst::TICKETS_NORMAL, $normal);
                        }
                    ),
                    new ClosureButton(
                        "ノーマルガチャ 10連続",
                        null,
                        function (Player $p, ClosureButton $button) use ($normal) {
                            self::sendGatyaConfirmForm($p, SQLConst::TICKETS_NORMAL, 10, $normal);
                        }
                    ),
                    new ClosureButton(
                        "クリスマスガチャ",
                        null,
                        function (Player $p, ClosureButton $button) use ($christmas) {
                            self::sendGatyaNumberChoices($p, SQLConst::TICKETS_CHRISTMAS_2021, $christmas);
                        }
                    ),
                );
            $p->sendForm($form);
        });
    }

    private static function sendGatyaNumberChoices(Player $p, string $ticketType, int $tickets)
    {
        $min = 1;
        if ($tickets < 1) $min = 0;
        $amount = new Slider(self::replaceTicketName($ticketType) . "を引く回数を選択してください", $min, $tickets, 1);
        $form = (new ClosureCustomForm(function (Player $p, ClosureCustomForm $form) use ($tickets, $ticketType, $amount): void {
            self::sendGatyaConfirmForm($p, $ticketType, $amount->getValue(), $tickets);
        }))->setTitle("Gatya")->addElements($amount);
        $p->sendForm($form);
    }

    private static function sendGatyaConfirmForm(Player $p, string $ticketType, int $num, int $tickets)
    {
        $after = $tickets - $num;
        $p->sendForm(
            (new ModalForm(
                new ClosureButton(
                    "はい",
                    null,
                    function (Player $p, ClosureButton $button) use ($ticketType, $num, $tickets) {
                        if ($tickets >= $num) {
                            switch ($ticketType) {
                                case SQLConst::TICKETS_NORMAL:
                                    NormalGatya::gatya($p, $num);
                                    break;

                                case SQLConst::TICKETS_CHRISTMAS_2021:
                                    Christmas2021::gatya($p, $num);
                                    break;

                                default:
                                    $p->sendMessage("エラーが発生しました");
                            }
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
                ->setText(self::replaceTicketName($ticketType) . "を$num 個消費してガチャを回しますか？\n$tickets -> $after")
        );
    }

    static function replaceTicketName(string $text): string
    {
        $text = str_replace(SQLConst::TICKETS_NORMAL, "ノーマルガチャチケット", $text);
        return str_replace(SQLConst::TICKETS_CHRISTMAS_2021, "クリスマスガチャチケット", $text);
    }
}
