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

namespace ree_jp\coral_reef\form\shop\data;

use bbo51dog\bboform\element\Dropdown;
use bbo51dog\bboform\element\Input;
use bbo51dog\bboform\form\ClosureCustomForm;
use bbo51dog\bboform\form\CustomForm;
use pocketmine\player\Player;
use pocketmine\world\Position;
use ree_jp\coral_reef\shop\data\DataShop;
use ree_jp\coral_reef\shop\data\DataShopData;
use ree_jp\coral_reef\shop\ShopStore;

class CustomTagShopManageForm
{
    const TYPE_BUY = "buy";
    const PAYMENT = ["money", "normal_tickets"];

    static function shopCreateForm(ShopStore $store, Position $pos): CustomForm
    {
        $typeElement = new Dropdown("このショップの支払い方法を設定してください", self::PAYMENT);
        $amountElement = new Input("このショップの支払う量を設定してください", "100");
        $categoryElement = new Input("カテゴリ 入力しなくてもいいよ", "ブロック");
        $subCategoryElement = new Input("サブカテゴリ 入力しなくてもいいよ", "ブロック");
        $dataValueElement = new Input("称号", "整地マスター");
        return (new ClosureCustomForm(function (Player $p) use (
            $dataValueElement, $subCategoryElement, $categoryElement,
            $pos, $store, $amountElement, $typeElement
        ): void {
            $payment = self::PAYMENT[$typeElement->getValue()];
            $amount = intval($amountElement->getValue());
            $category = $categoryElement->getValue();
            $subCategory = $subCategoryElement->getValue();
            $dataValue = $dataValueElement->getValue();

            $store->createShop(new DataShop($pos, new DataShopData("custom_tag", "", $dataValue, 1), "カスタム称号", 1,
                self::TYPE_BUY, ["type" => $payment, "amount" => $amount], 0, [], $category, $subCategory));
            $p->sendMessage("ショップを作成しました");
        }))->setTitle("ItemShop Edit")->addElements($typeElement, $amountElement, $categoryElement, $subCategoryElement, $dataValueElement);
    }
}