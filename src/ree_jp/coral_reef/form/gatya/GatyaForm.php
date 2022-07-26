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

namespace ree_jp\coral_reef\form\gatya;

use bbo51dog\bboform\element\ClosureButton;
use bbo51dog\bboform\element\Slider;
use bbo51dog\bboform\form\ClosureCustomForm;
use bbo51dog\bboform\form\ModalForm;
use bbo51dog\bboform\form\SimpleForm;
use pocketmine\player\Player;
use ree_jp\coral_reef\gatya\event\Summer2022;
use ree_jp\coral_reef\gatya\NormalGatya;
use ree_jp\coral_reef\sql\mysql\SQLRepository;
use ree_jp\coral_reef\sql\SQLConst;

class GatyaForm
{
    static function sendForm(SQLRepository $repo, Player $p): void
    {
        $repo->getAllSubtypeValue($p->getXuid(), SQLConst::TYPE_TICKETS, function (array $rows) use ($repo, $p) {
            if (!$p->isOnline()) return;
            $normal = 0;
            $summer = 0;
            foreach ($rows as $row) {
                if ($row['subtype'] === SQLConst::TICKETS_NORMAL) $normal = $row['value'];
                if ($row['subtype'] === SQLConst::TICKETS_SUMMER_2022) $summer = $row['value'];
            }
            $form = (new SimpleForm())
                ->setTitle("Menu -> Gatya")
                ->setText("ノーマルガチャチケット: $normal 個\nサマーガチャチケット: $summer 個")
                ->addElements(
                    new ClosureButton(
                        "ノーマルガチャ", null,
                        function (Player $p) use ($repo, $normal) {
                            self::sendGatyaNumberChoices($repo, $p, SQLConst::TICKETS_NORMAL, $normal);
                        }
                    ),
                    new ClosureButton(
                        "ノーマルガチャ 10連続", null,
                        function (Player $p) use ($repo, $normal) {
                            self::sendGatyaConfirmForm($repo, $p, SQLConst::TICKETS_NORMAL, 10, $normal);
                        }
                    ),
                    new ClosureButton(
                        "クリスマスガチャ", null,
                        function (Player $p) use ($repo, $summer) {
                            self::sendGatyaNumberChoices($repo, $p, SQLConst::TICKETS_SUMMER_2022, $summer);
                        }
                    ),
                    new ClosureButton(
                        "ガチャ履歴", null,
                        function (Player $p) use ($repo, $summer) {
                            GatyaHistoryForm::sendForm($p, $repo);
                        }
                    ),
                    new ClosureButton("ガチャとは", null,
                        function (Player $p) {
                            $p->getServer()->dispatchCommand($p, "exe-p wp-view category 110");
                        }
                    ),
                );
            $p->sendForm($form);
        });
    }

    private static function sendGatyaNumberChoices(SQLRepository $repo, Player $p, string $ticketType, int $tickets): void
    {
        $min = 1;
        if ($tickets < 1) $min = 0;
        $amount = new Slider(self::replaceTicketName($ticketType) . "を引く回数を選択してください", $min, $tickets, $min);
        $form = new ClosureCustomForm(function (Player $p) use ($repo, $tickets, $ticketType, $amount): void {
            self::sendGatyaConfirmForm($repo, $p, $ticketType, $amount->getValue(), $tickets);
        });
        $form->setTitle("Gatya")->addElements($amount);
        $p->sendForm($form);
    }

    static function replaceTicketName(string $text): string
    {
        $text = str_replace(SQLConst::TICKETS_NORMAL, "ノーマルガチャチケット", $text);
        return str_replace(SQLConst::TICKETS_SUMMER_2022, "サマーガチャチケット", $text);
    }

    private static function sendGatyaConfirmForm(SQLRepository $repo, Player $p, string $ticketType, int $num, int $tickets): void
    {
        if ($num > 50) {
            $p->sendMessage("50連が限界です");
            return;
        }
        $after = $tickets - $num;
        $form = new ModalForm(
            new ClosureButton(
                "はい", null,
                function (Player $p) use ($repo, $ticketType, $num, $tickets) {
                    if ($tickets >= $num) {
                        switch ($ticketType) {
                            case SQLConst::TICKETS_NORMAL:
                                NormalGatya::gatya($repo, $p, $num);
                                break;

                            case SQLConst::TICKETS_SUMMER_2022:
                                Summer2022::gatya($repo, $p, $num);
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
                "いいえ", null,
                function (Player $p) use ($repo) {
                    self::sendForm($repo, $p);
                }
            )
        );
        $form->setTitle("Gatya -> Confirm")->setText(self::replaceTicketName($ticketType) .
            "を$num 個消費してガチャを回しますか？\n$tickets -> $after");
        $p->sendForm($form);
    }
}
