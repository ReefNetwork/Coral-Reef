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

use bbo51dog\bboform\element\ClosureButton;
use bbo51dog\bboform\element\Input;
use bbo51dog\bboform\element\Label;
use bbo51dog\bboform\element\Slider;
use bbo51dog\bboform\form\ClosureCustomForm;
use bbo51dog\bboform\form\ModalForm;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use ree_jp\coral_reef\shop\data\DataShop;
use ree_jp\coral_reef\shop\data\DataShopService;
use ree_jp\coral_reef\shop\ShopService;
use ree_jp\coral_reef\shop\ShopStore;
use ree_jp\coral_reef\sql\mysql\SQLRepository;
use ree_jp\reef_edge\ReefEdgePlugin;
use ree_jp\reef_edge\socket\SocketData;

class DataShopDetailForm
{
    static function sendForm(SQLRepository $repo, ShopStore $store, Player $p, DataShop $shop): void
    {
        $dataArray = ["xuid" => $p->getXuid(), "type" => $shop->data->type, "subType" => $shop->data->subtype, "item" => $shop->data->value];
        ReefEdgePlugin::$socketClient->send(new SocketData("item-count", $dataArray), function (array $result) use ($shop, $store, $repo, $p): void {
            if (!$p->isOnline()) return;
            if (!$result["isSuccess"] || !isset($result["count"])) {
                $p->sendMessage("エラーが発生しました");
                return;
            }
            self::sendDetailForm($repo, $store, $p, $shop, $result["count"]);
        });

    }

    static function sendDetailForm(SQLRepository $repo, ShopStore $store, Player $p, DataShop $shop, int $count): void
    {
        $itemString = "\n\nデータ\n{$shop->showName}「{$shop->data->value}」× 1 (メタ情報:{$shop->data->type}.{$shop->data->subtype})";
        $isSlide = !$p->isSneaking();

        $text = "金額\n" . $shop->payment["type"] . $shop->payment["amount"] . TextFormat::RESET;
        $maxAmount = 64;

        if ($shop->haveLimit > 0) {
            $text = $text . "\n\n所持制限制限\n$count /" . $shop->haveLimit;
            $remain = $shop->haveLimit - $count;
            if ($remain >= 1 && $maxAmount > $remain) {
                $maxAmount = $remain;
            }
        }
        if ($shop->dayLimit > 0) {
            $dayCount = $shop->getDayLimitCounter($p->getXuid());

            $text = $text . "\n\n1日の" . ShopService::replaceOrderType($shop->orderType) . "制限\n$dayCount /" . $shop->dayLimit;
            $remain = $shop->dayLimit - $dayCount;
            if ($remain >= 1 && $maxAmount > $remain) {
                $maxAmount = $remain;
            }
        }

        $amountSlide = new Slider(ShopService::replaceOrderType($shop->orderType) . "するセット数を選択してください", 1, $maxAmount, 1);
        $amountInput = new Input(ShopService::replaceOrderType($shop->orderType) . "するセット数を入力してください", 10);

        $form = (new ClosureCustomForm(function (Player $p) use ($count, $amountInput, $isSlide, $store, $repo, $shop, $amountSlide): void {
            if ($isSlide) {
                $amount = $amountSlide->getValue();
            } else {
                $amount = intval($amountInput->getValue());
            }
            if ($amount <= 0) {
                $p->sendMessage("0以下は指定できません");
                return;
            }

            $p->sendForm((new ModalForm(new ClosureButton(ShopService::replaceOrderType($shop->orderType) . "する", null,
                function (Player $p) use ($amount, $store, $repo, $shop): void {
                    switch ($shop->orderType) {
                        case "buy":
                            DataShopService::buy($repo, $store, $shop, $p, $amount);
                            break;
                        case "sell":
                            DataShopService::sell($repo, $store, $shop, $p, $amount);
                            break;
                        default:
                            $p->sendMessage("エラーが発生しました");
                    }
                }), new ClosureButton("戻る", null, function (Player $p) use ($count, $store, $repo, $shop): void {
                self::sendDetailForm($repo, $store, $p, $shop, $count);
            })))->setTitle("ItemShop -> Confirm")->setText("本当にこのデータを" . ShopService::replaceOrderType($shop->orderType) . "しますか?\n" .
                ShopService::replacePaymentType("金額\n" . $shop->payment["type"] . $shop->payment["amount"] * $amount)));

        }))->setTitle("ItemShop")->addElement(new Label(ShopService::replacePaymentType($text) . $itemString));

        // スニークしてる時は数を入力できるように
        if ($isSlide) {
            $form->addElement($amountSlide);
        } else {
            $form->addElement($amountInput);
        }

        $p->sendForm($form);
    }

}