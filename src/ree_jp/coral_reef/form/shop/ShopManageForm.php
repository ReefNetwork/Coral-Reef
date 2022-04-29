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

namespace ree_jp\coral_reef\form\shop\item;

use bbo51dog\bboform\element\ClosureButton;
use bbo51dog\bboform\form\SimpleForm;
use pocketmine\player\Player;
use pocketmine\world\Position;
use ree_jp\coral_reef\form\shop\data\DataShopManageForm;
use ree_jp\coral_reef\shop\data\DataShop;
use ree_jp\coral_reef\shop\item\ItemShop;
use ree_jp\coral_reef\shop\ShopStore;

class ShopManageForm
{
    static function sendForm(Player $p, ShopStore $store, Position $pos): void
    {
        $shop = $store->findShop($pos);
        if (is_null($shop)) {
            self::sendShopChooseForm($p, $store, $pos);
        } else {
            $p->sendForm((new SimpleForm())->setTitle("Shop Manage")->setText("このショップに対して行う動作を選んでください")
                ->addElements(new ClosureButton("ショップを変更する", null,
                    function (Player $p) use ($pos, $store, $shop): void {
                        if ($shop instanceof ItemShop) {
                            $p->sendForm(ItemShopManageForm::shopCreateForm($store, $pos, $shop->orderType, $shop->payment["type"], $shop->payment["amount"], $shop->dayLimit,
                                $shop->category, $shop->subCategory));
                        }
                        if ($shop instanceof DataShop) {
                            $p->sendForm(DataShopManageForm::shopCreateForm($store, $pos, $shop->orderType, $shop->payment["type"], $shop->payment["amount"], $shop->dayLimit,
                                $shop->category, $shop->subCategory, $shop->data->type, $shop->data->subtype, $shop->data->value, $shop->haveLimit));
                        }
                    }
                ), new ClosureButton("毎日の購入制限記録をリセットする", null, function (Player $p) use ($store, $shop) {
                    $shop->resetDayLimitCounter($store);
                    $p->sendMessage("リセットしました");
                }), new ClosureButton("削除する", null, function () use ($pos, $store) {
                    $store->removeShop($pos);
                })));
        }
    }

    static function sendShopChooseForm(Player $p, ShopStore $store, Position $pos): void
    {
        $form = new SimpleForm();
        $form->setTitle("Shop Choose")->setText("作成したいショップを選択してください");
        $form->addElements(
            new ClosureButton("アイテムショップ", null, function () use ($pos, $store, $p): void {
                $p->sendForm(ItemShopManageForm::shopCreateForm($store, $pos));
            }),
            new ClosureButton("データショップ", null, function () use ($pos, $store, $p): void {
                $p->sendForm(DataShopManageForm::shopCreateForm($store, $pos));
            })
        );
    }
}