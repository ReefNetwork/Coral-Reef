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

use bbo51dog\bboform\element\Label;
use bbo51dog\bboform\element\Slider;
use bbo51dog\bboform\form\ClosureCustomForm;
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
        $text = new Label("金額\n" . TextFormat::GOLD . $shop->payment["amount"] . TextFormat::RESET . $itemString);
        $amount = new Slider("購入するセット数を選択してください", 1, 64, 1);
        $form = (new ClosureCustomForm(function (Player $p, ClosureCustomForm $form) use ($shop, $amount): void {
            $amount->getValue();
            $shop->buy($p, $amount->getValue());
        }))->setTitle("Shop")->addElements($text, $amount);
        $p->sendForm($form);
    }
}
