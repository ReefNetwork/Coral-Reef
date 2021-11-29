<?php
/*
 *  CCCCC                        lll RRRRRR                 fff
 * CC    C  oooo  rr rr    aa aa lll RR   RR   eee    eee  ff
 * CC      oo  oo rrr  r  aa aaa lll RRRRRR  ee   e ee   e ffff
 * CC    C oo  oo rr     aa  aaa lll RR  RR  eeeee  eeeee  ff
 *  CCCCC   oooo  rr      aaa aa lll RR   RR  eeeee  eeeee ff
 *
 * Copyright (c) 2021. Ree-jp(https://ree-jp.net)
 */

namespace ree_jp\coral_reef\shop\form;

use bbo51dog\bboform\element\ClosureButton;
use bbo51dog\bboform\element\Label;
use bbo51dog\bboform\element\Slider;
use bbo51dog\bboform\form\ClosureCustomForm;
use bbo51dog\bboform\form\ModalForm;
use pocketmine\Player;
use pocketmine\utils\TextFormat;
use ree_jp\coral_reef\shop\Shop;

class ShopDetailForm
{
    static function sendForm(Player $p, Shop $shop): void
    {
        $itemString = "\n\nアイテム\n";
        $items = $shop->getItems();
        if (is_null($items)) {
            $p->sendMessage("ショップのアイテムが見つかりませんでした");
            return;
        }
        foreach ($items as $item) {
            $itemString .= $item->getName() . TextFormat::RESET . " ×" . $item->getCount() . "\n";
        }
        $text = "金額\n" . $shop->payment["type"] . $shop->payment["amount"] . TextFormat::RESET;
        $amount = new Slider("購入するセット数を選択してください", 1, 64, 1);

        $form = (new ClosureCustomForm(function (Player $p, ClosureCustomForm $form) use ($shop, $amount): void {
            $p->sendForm((new ModalForm(new ClosureButton("購入する", null, function (Player $p, ClosureButton $button) use ($amount, $shop): void {
                $shop->buy($p, $amount->getValue());
            }), new ClosureButton("戻る", null, function (Player $p, ClosureButton $button) use ($shop): void {
                self::sendForm($p, $shop);
            })))->setTitle("Shop -> Confirm")->setText("本当にこのアイテムを購入しますか?\n必要な金額: " .
                self::replacePaymentType("金額\n" . $shop->payment["type"] . $shop->payment["amount"] * $amount->getValue() . TextFormat::RESET)));

        }))->setTitle("Shop")->addElements(new Label(self::replacePaymentType($text) . $itemString), $amount);
        $p->sendForm($form);
    }

    static function replacePaymentType(string $text): string
    {
        $text = str_replace("money", "お金: " . TextFormat::GOLD, $text);
        return str_replace("normal_tickets", "ガチャチケット: " . TextFormat::BLUE, $text);
    }
}
