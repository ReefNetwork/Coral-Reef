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

use bbo51dog\bboform\element\ButtonImage;
use bbo51dog\bboform\element\ClosureButton;
use bbo51dog\bboform\element\Slider;
use bbo51dog\bboform\form\ClosureCustomForm;
use bbo51dog\bboform\form\ModalForm;
use bbo51dog\bboform\form\SimpleForm;
use pocketmine\player\Player;
use ree_jp\coral_reef\gatya\GatyaService;
use ree_jp\coral_reef\gatya\NormalGatya;
use ree_jp\coral_reef\sql\mysql\SQLRepository;
use ree_jp\coral_reef\sql\SQLConst;

class GatyaForm
{
    const NOW_GATYA = [SQLConst::LOG_GATYA];

    static function sendForm(SQLRepository $repo, Player $p, string $gatyaType = null): void
    {
        if ($gatyaType == null) {
            $gatyaType = current(self::NOW_GATYA);
        }
        $repo->getAllSubtypeValue($p->getXuid(), SQLConst::TYPE_TICKETS, function (array $rows) use ($gatyaType, $repo, $p) {
            if (!$p->isOnline()) return;
            $ticketCount = 0;
            foreach ($rows as $row) {
                if ($row['subtype'] === GatyaService::GATYA[$gatyaType]["ticket"]) $ticketCount = $row['value'];
            }
            $gatya_image = GatyaService::GATYA[$gatyaType]["image"];
            $form = (new SimpleForm())
                ->setTitle("[dynamic_seichi_gatya]$gatya_image")
                ->setText(GatyaService::ticketName(GatyaService::GATYA[$gatyaType]["ticket"]) . ": $ticketCount 個")
                ->addElements(
                    new ClosureButton("[gatya_close]", null, function (): void {
                    }),
                    new ClosureButton("[gatya_info]詳細", null, function () use ($gatyaType, $p) {
                        $p->getServer()->dispatchCommand($p, "exe-p wp-view post " . GatyaService::GATYA[$gatyaType]["details"]);
                    }),
                    new ClosureButton("[gatya_info]履歴", null, function () use ($p, $repo) {
                        GatyaHistoryForm::sendForm($p, $repo);
                    }),
                    new ClosureButton("[gatya_run]ガチャを引く", null, function () use ($gatyaType, $p, $repo, $ticketCount): void {
                        self::sendGatyaNumberChoices($repo, $p, $gatyaType, $ticketCount);
                    }),
                    new ClosureButton("[gatya_run]ガチャを10回引く", null, function () use ($gatyaType, $p, $repo, $ticketCount): void {
                        self::sendGatyaConfirmForm($repo, $p, $gatyaType, 10, $ticketCount);
                    })
                );
            foreach (self::NOW_GATYA as $gatya) {
                $rand = array_rand(GatyaService::GATYA[$gatya]["pick_up_image"]);
                $form->addElement(new ClosureButton("[gatya_select]", new ButtonImage(ButtonImage::TYPE_PATH, GatyaService::GATYA[$gatya]["pick_up_image"][$rand]),
                    function () use ($repo, $p, $gatya): void {
                        self::sendForm($repo, $p, $gatya);
                    }
                ));
            }
            $p->sendForm($form);
        });
    }

    private static function sendGatyaNumberChoices(SQLRepository $repo, Player $p, string $gatyaType, int $tickets): void
    {
        $min = 1;
        if ($tickets < 1) $min = 0;
        $max = 50;
        if ($tickets < $max) $max = $tickets;
        $amount = new Slider(GatyaService::GATYA[$gatyaType]["name"] . "を引く回数を選択してください", $min, $max, $min);
        $form = new ClosureCustomForm(function (Player $p) use ($repo, $tickets, $gatyaType, $amount): void {
            self::sendGatyaConfirmForm($repo, $p, $gatyaType, $amount->getValue(), $tickets);
        });
        $form->setTitle("Gatya")->addElements($amount);
        $p->sendForm($form);
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
        $form->setTitle("Gatya -> Confirm")->setText("同時にガチャを引く行為は§c禁止§rです\n連続でガチャを引きたい場合は必ず全部引き終えてからガチャを引くようにお願いします\n"
            . GatyaService::GATYA[$gatyaType]["name"] . "を$num 個消費してガチャを回しますか？\n$tickets -> $after");
        $p->sendForm($form);
    }
}
