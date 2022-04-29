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

class DataShopManageForm
{
    const TYPE = ["buy", "sell"];
    const PAYMENT = ["money", "normal_tickets"];

    static function shopCreateForm(ShopStore $store, Position $pos, string $orderType = "なし", string $payType = "なし", int $amount = 0,
                                   int       $dayLimit = 0, string $category = "", string $subCategory = "", string $dataType = "", string $dataSubType = "",
                                   string    $dataValue = "", int $haveLimit = 0, string $showName = ""): CustomForm
    {
        $orderElement = new Dropdown("買わせる(buy)か買い取る(sell)か (もともとは$orderType)", self::TYPE);
        $typeElement = new Dropdown("このショップの支払い方法を設定してください(もともとは$payType)", self::PAYMENT);
        $amountElement = new Input("このショップの支払う量を設定してください", "100", $amount);
        $dayLimitElement = new Input("一日に買える量(もともとは$dayLimit)0に設定すると無限", "0", $dayLimit);
        $categoryElement = new Input("カテゴリ(もともとは$category) 入力しなくてもいいよ", "ブロック", $category);
        $subCategoryElement = new Input("サブカテゴリ(もともとは$category) 入力しなくてもいいよ", "ブロック", $subCategory);
        $dataTypeElement = new Input("データタイプ(もともとは$dataType)", "ブロック", $dataType);
        $dataSubTypeElement = new Input("データサブタイプ(もともとは$dataSubType) 入力しなくてもいいよ", "", $dataSubType);
        $dataValueElement = new Input("データ値(もともとは$dataValue) 入力しなくてもいいよ", "整地マスター", $dataValue);
        $havaLimitElement = new Input("所持数制限(もともとは$haveLimit) 入力しなくてもいいよ0に設定すると無限", "1", $haveLimit);
        $showNameElement = new Input("このデータのショップに表示される名前(もともとは$haveLimit)", "称号", $showName);
        return (new ClosureCustomForm(function (Player $p) use (
            $showNameElement, $havaLimitElement, $dataValueElement, $dataSubTypeElement, $dataTypeElement, $subCategoryElement, $categoryElement,
            $dayLimitElement, $orderElement, $pos, $store, $amountElement, $typeElement
        ): void {
            $order = self::TYPE[$orderElement->getValue()];
            $payment = self::PAYMENT[$typeElement->getValue()];
            $amount = intval($amountElement->getValue());
            $dayLimit = intval($dayLimitElement->getValue());
            $category = $categoryElement->getValue();
            $subCategory = $subCategoryElement->getValue();
            $dataType = $dataTypeElement->getValue();
            $dataSubType = $dataSubTypeElement->getValue();
            $dataValue = $dataValueElement->getValue();
            $haveLimit = intval($havaLimitElement->getValue());
            $showName = $showNameElement->getValue();

            $store->createShop(new DataShop($pos, new DataShopData($dataType, $dataSubType, $dataValue, 1), $showName, $haveLimit, $order, ["type" => $payment, "amount" => $amount],
                $dayLimit, [], $category, $subCategory));
            $p->sendMessage("ショップを作成しました");
        }))->setTitle("ItemShop Edit")->addElements($orderElement, $typeElement, $amountElement, $dayLimitElement, $categoryElement,
            $subCategoryElement, $dataTypeElement, $dataSubTypeElement, $dataValueElement, $havaLimitElement, $showNameElement);
    }
}