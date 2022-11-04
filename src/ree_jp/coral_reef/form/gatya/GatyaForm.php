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
use pocketmine\utils\TextFormat;
use ree_jp\coral_reef\gatya\event\HalloweenNight;
use ree_jp\coral_reef\gatya\event\HalloweenParty;
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
            foreach ($rows as $row) {
                if ($row['subtype'] === SQLConst::TICKETS_NORMAL) $normal = $row['value'];
            }
            $form = (new SimpleForm())
                ->setTitle("Menu -> Gatya")
                ->setText("ノーマルガチャチケット: $normal 個")
                ->addElements(
                    new ClosureButton(
                        TextFormat::GOLD . "Halloween" . TextFormat::DARK_PURPLE . "Night" . TextFormat::RESET . "ガチャ", null,
                        function (Player $p) use ($repo, $normal) {
                            self::sendGatyaNumberChoices($repo, $p, SQLConst::LOG_GATYA_HALLOWEEN_NIGHT, $normal);
                        }
                    ),
                    new ClosureButton(
                        TextFormat::GOLD . "Halloween" . TextFormat::DARK_GREEN . "Party" . TextFormat::RESET . "ガチャ", null,
                        function (Player $p) use ($repo, $normal) {
                            self::sendGatyaNumberChoices($repo, $p, SQLConst::LOG_GATYA_HALLOWEEN_PARTY, $normal);
                        }
                    ),
                    new ClosureButton(
                        "ノーマルガチャ", null,
                        function (Player $p) use ($repo, $normal) {
                            self::sendGatyaNumberChoices($repo, $p, SQLConst::LOG_GATYA, $normal);
                        }
                    ),
                    new ClosureButton(
                        "ノーマルガチャ 10連", null,
                        function (Player $p) use ($repo, $normal) {
                            self::sendGatyaConfirmForm($repo, $p, SQLConst::LOG_GATYA, 10, $normal);
                        }
                    ),
                    new ClosureButton(
                        "ガチャ履歴", null,
                        function (Player $p) use ($repo) {
                            GatyaHistoryForm::sendForm($p, $repo);
                        }
                    ),
                    new ClosureButton("ガチャ詳細", null,
                        function (Player $p) {
                            $p->getServer()->dispatchCommand($p, "exe-p wp-view category 110");
                        }
                    ),
                );
            $p->sendForm($form);
        });
    }

    private static function sendGatyaNumberChoices(SQLRepository $repo, Player $p, string $gatyaType, int $tickets): void
    {
        $min = 1;
        if ($tickets < 1) $min = 0;
        $max = 50;
        if ($tickets < 50) $max = $tickets;
        $amount = new Slider(self::replaceGatyaName($gatyaType) . "を引く回数を選択してください", $min, $max, $min);
        $form = new ClosureCustomForm(function (Player $p) use ($repo, $tickets, $gatyaType, $amount): void {
            self::sendGatyaConfirmForm($repo, $p, $gatyaType, $amount->getValue(), $tickets);
        });
        $form->setTitle("Gatya")->addElements($amount);
        $p->sendForm($form);
    }

    static function replaceGatyaName(string $text): string
    {
        if (isset(GatyaHistoryForm::GATYA_LIST[$text])) return GatyaHistoryForm::GATYA_LIST[$text];
        return $text;
    }

    private static function sendGatyaConfirmForm(SQLRepository $repo, Player $p, string $gatyaType, int $num, int $tickets): void
    {
        if ($num > 50) {
            $p->sendMessage("50連が限界です");
            return;
        }
        $after = $tickets - $num;
        $form = new ModalForm(
            new ClosureButton(
                "はい", null,
                function (Player $p) use ($repo, $gatyaType, $num, $tickets) {
                    if ($tickets >= $num) {
                        switch ($gatyaType) {
                            case SQLConst::LOG_GATYA_HALLOWEEN_NIGHT:
                                HalloweenNight::gatya($repo, $p, $num);
                                break;

                            case SQLConst::LOG_GATYA_HALLOWEEN_PARTY:
                                HalloweenParty::gatya($repo, $p, $num);
                                break;

                            case SQLConst::LOG_GATYA:
                                NormalGatya::gatya($repo, $p, $num);
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
        $form->setTitle("Gatya -> Confirm")->setText(self::replaceGatyaName($gatyaType) .
            "を$num 個消費してガチャを回しますか？\n$tickets -> $after");
        $p->sendForm($form);
    }
}
