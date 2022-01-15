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
use bbo51dog\bboform\element\Dropdown;
use bbo51dog\bboform\element\Input;
use bbo51dog\bboform\form\ClosureCustomForm;
use bbo51dog\bboform\form\CustomForm;
use bbo51dog\bboform\form\SimpleForm;
use pocketmine\player\Player;
use pocketmine\world\Position;
use ree_jp\coral_reef\shop\Shop;
use ree_jp\coral_reef\shop\ShopStore;

class ShopManageForm
{
    const TYPE = ["buy", "sell"];
    const PAYMENT = ["money", "normal_tickets"];

    static function sendForm(Player $p, ShopStore $store, Position $pos): void
    {
        $shop = $store->findShop($pos);
        if (is_null($shop)) {
            $p->sendForm(self::shopCreateForm($store, $pos));
        } else {
            $p->sendForm((new SimpleForm())->setTitle("Shop Manage")->setText("このショップに対して行う動作を選んでください")
                ->addElements(new ClosureButton("ショップを変更する", null,
                    function (Player $p) use ($pos, $store, $shop): void {
                        $p->sendForm(self::shopCreateForm($store, $pos, $shop->orderType, $shop->payment["type"], $shop->payment["amount"]));
                    }
                ), new ClosureButton("毎日の購入制限記録をリセットする", null, function (Player $p) use ($store, $shop) {
                    $shop->resetDayLimitCounter($store);
                    $p->sendMessage("リセットしました");
                }), new ClosureButton("削除する", null, function () use ($pos, $store) {
                    $store->removeShop($pos);
                })));
        }
    }

    static function shopCreateForm(ShopStore $store, Position $pos, string $orderType = "なし", string $payType = "なし", int $amount = 0,
                                   int       $dayLimit = 0): CustomForm
    {
        $orderElement = new Dropdown("買わせる(buy)か買い取る(sell)か (もともとは$orderType)", self::TYPE);
        $typeElement = new Dropdown("このショップの支払い方法を設定してください(もともとは$payType)", self::PAYMENT);
        $amountElement = new Input("このショップの支払う量を設定してください", "100", $amount);
        $dayLimitElement = new Input("一日に買える量(もともとは$dayLimit)0に設定すると無限", "0", $amount);
        return (new ClosureCustomForm(function (Player $p) use ($dayLimitElement, $orderElement, $pos, $store, $amountElement, $typeElement): void {
            $order = self::TYPE[$orderElement->getValue()];
            $payment = self::PAYMENT[$typeElement->getValue()];
            $amount = intval($amountElement->getValue());
            $dayLimit = intval($dayLimitElement->getValue());

            $store->createShop(new Shop($pos, $order, ["type" => $payment, "amount" => $amount], $dayLimit, []));
            $p->sendMessage("ショップを作成しました");
        }))->setTitle("Shop Edit")->addElements($orderElement, $typeElement, $amountElement);
    }
}
