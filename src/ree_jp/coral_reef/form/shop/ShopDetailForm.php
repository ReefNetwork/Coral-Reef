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

namespace ree_jp\coral_reef\form\shop;

use bbo51dog\bboform\element\ClosureButton;
use bbo51dog\bboform\element\Input;
use bbo51dog\bboform\element\Label;
use bbo51dog\bboform\element\Slider;
use bbo51dog\bboform\element\Toggle;
use bbo51dog\bboform\form\ClosureCustomForm;
use bbo51dog\bboform\form\ModalForm;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use ree_jp\coral_reef\shop\Shop;
use ree_jp\coral_reef\shop\ShopService;
use ree_jp\coral_reef\shop\ShopStore;
use ree_jp\coral_reef\sql\SQLRepository;

class ShopDetailForm
{
    static function sendForm(SQLRepository $repo, ShopStore $store, Player $p, Shop $shop): void
    {
        $itemString = "\n\nアイテム\n";
        $isSlide = !$p->isSneaking();
        $items = $shop->getItems();
        if (is_null($items)) {
            $p->sendMessage("ショップのアイテムが見つかりませんでした");
            return;
        }
        foreach ($items as $item) {
            $itemString .= $item->getName() . TextFormat::RESET . " ×" . $item->getCount() . "\n";
        }

        $text = "金額\n" . $shop->payment["type"] . $shop->payment["amount"] . TextFormat::RESET;
        $maxAmount = 64;

        if ($shop->dayLimit > 0) {
            $dayCount = $shop->getDayLimitCounter($p->getXuid());

            $text = $text . "\n\n1日の" . self::replaceOrderType($shop->orderType) . "制限\n$dayCount /" . $shop->dayLimit;
            $remain = $shop->dayLimit - $dayCount;
            if ($remain >= 1) {
                $maxAmount = $remain;
            }
        }

        $amountSlide = new Slider(self::replaceOrderType($shop->orderType) . "するセット数を選択してください", 1, $maxAmount, 1);
        $amountInput = new Input(self::replaceOrderType($shop->orderType) . "するセット数を入力してください", 10);
        $isDirectStorage = new Toggle("直接ストレージ内にアイテムをいれますか?");
        $isDirectSell = new Toggle("ストレージ内のアイテムも売りますか?");

        $form = (new ClosureCustomForm(function (Player $p) use ($isDirectSell, $amountInput, $isSlide, $isDirectStorage, $store, $repo, $shop, $amountSlide): void {
            if ($isSlide) {
                $amount = $amountSlide->getValue();
            } else {
                $amount = intval($amountInput->getValue());
                if ($amount <= 0) {
                    $p->sendMessage("0以下は指定できません");
                    return;
                }
            }

            $p->sendForm((new ModalForm(new ClosureButton(self::replaceOrderType($shop->orderType) . "する", null,
                function (Player $p) use ($isDirectSell, $amount, $isDirectStorage, $store, $repo, $shop): void {
                    switch ($shop->orderType) {
                        case "buy":
                            ShopService::buy($repo, $store, $shop, $p, $amount, $isDirectStorage->getValue());
                            break;
                        case "sell":
                            ShopService::sell($repo, $store, $shop, $p, $amount, $isDirectSell->getValue());
                            break;
                        default:
                            $p->sendMessage("エラーが発生しました");
                    }
                }), new ClosureButton("戻る", null, function (Player $p) use ($store, $repo, $shop): void {
                self::sendForm($repo, $store, $p, $shop);
            })))->setTitle("Shop -> Confirm")->setText("本当にこのアイテムを" . self::replaceOrderType($shop->orderType) . "しますか?\n" .
                self::replacePaymentType("金額\n" . $shop->payment["type"] . $shop->payment["amount"] * $amount)));

        }))->setTitle("Shop")->addElement(new Label(self::replacePaymentType($text) . $itemString));

        // スニークしてる時は数を入力できるように
        if ($isSlide) {
            $form->addElement($amountSlide);
        } else {
            $form->addElement($amountInput);
        }
        // orderTypeがbuyの時のみformに登録する
        if ($shop->orderType === "buy") {
            $form->addElement($isDirectStorage);
        }
        if ($shop->orderType === "buy") {
            $form->addElement($isDirectSell);
        }

        $p->sendForm($form);
    }

    static function replaceOrderType(string $text): string
    {
        $text = str_replace("buy", TextFormat::GREEN . "購入" . TextFormat::RESET, $text);
        return str_replace("sell", TextFormat::RED . "売却" . TextFormat::RESET, $text);
    }

    static function replacePaymentType(string $text): string
    {
        $text = str_replace("money", TextFormat::GOLD . "お金: " . TextFormat::RESET, $text);
        return str_replace("normal_tickets", TextFormat::BLUE . "ガチャチケット: " . TextFormat::RESET, $text);
    }
}
