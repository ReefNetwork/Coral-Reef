<?php
/*
 *  CCCCC                        lll RRRRRR                 fff
 * CC    C  oooo  rr rr    aa aa lll RR   RR   eee    eee  ff
 * CC      oo  oo rrr  r  aa aaa lll RRRRRR  ee   e ee   e ffff
 * CC    C oo  oo rr     aa  aaa lll RR  RR  eeeee  eeeee  ff
 *  CCCCC   oooo  rr      aaa aa lll RR   RR  eeeee  eeeee ff
 *
 * Copyright (c) 2023. Ree-jp(https://ree-jp.net)
 */

namespace ree_jp\coral_reef\form\account;

use bbo51dog\bboform\element\ClosureButton;
use bbo51dog\bboform\element\Input;
use bbo51dog\bboform\form\ClosureCustomForm;
use bbo51dog\bboform\form\ModalForm;
use pocketmine\player\Player;
use ree_jp\coral_reef\account\AccountService;
use ree_jp\coral_reef\account\AccountStore;
use ree_jp\coral_reef\money\MoneyService;
use ree_jp\coral_reef\sql\mysql\SQLRepository;
use ree_jp\coral_reef\sql\RepositoryPool;
use ree_jp\coral_reef\StoreHouse;
use ree_jp\reef_edge\ReefEdgePlugin;
use ree_jp\reef_edge\socket\SocketData;

class MoneyForm
{
    static function sendForm(Player $p, StoreHouse $house, RepositoryPool $pool, string $target = ""): void
    {
        $to = new Input("送金したい人の名前を入力してください", "名前", $target);
        $amountInput = new Input("送金したい金額を入力してください", "数字");
        $form = new ClosureCustomForm(function () use ($pool, $amountInput, $p, $to, $house): void {
            /** @var AccountStore $store */
            $store = $house->get(AccountStore::class);
            $targetName = $to->getValue();
            $xuid = $store->getXuid($targetName);
            if ($xuid == null) {
                $p->sendMessage("{$to->getValue()}さんは見つかりませんでした");
                return;
            }

            $amount = intval($amountInput->getValue());
            if ($amount <= 0) {
                $p->sendMessage("0円以下は送金出来ません");
                return;
            }

            self::sendConfirmForm($p, $targetName, $xuid, $amount, $house, $pool);
        });
        $form->setTitle("Money -> Transfer")->addElements($to, $amountInput);
        $p->sendForm($form);
    }

    static function sendConfirmForm(Player $p, string $targetName, string $targetXuid, int $amount, StoreHouse $house, RepositoryPool $pool): void
    {
        $form = (new ModalForm(new ClosureButton("送金する", null, function () use ($targetXuid, $amount, $p, $pool): void {
            /** @var $sqlRepo SQLRepository */
            $sqlRepo = $pool->get(SQLRepository::class);
            MoneyService::getMoney($sqlRepo, $p->getXuid(), function (int $money) use ($targetXuid, $sqlRepo, $p, $amount): void {
                if (!$p->isOnline()) return;

                if ($amount > $money) {
                    $p->sendMessage("お金が足りません");
                    return;
                }

                MoneyService::reduceMoney($sqlRepo, $p->getXuid(), $amount);
                MoneyService::addMoney($sqlRepo, $targetXuid, $amount);
                $p->sendMessage("送金しました");

                $target = AccountService::getPlayerByXuid($targetXuid);
                if ($target instanceof Player) $target->sendMessage($p->getName() . "さんから§6" . MoneyService::moneyFormat($amount) . "円§r送金されました");
                ReefEdgePlugin::$socketClient->send(new SocketData("discord-message", ["message" => $p->getName() . " => " . $target->getName() . " : " . MoneyService::moneyFormat($amount),
                    "channelID" => "1118893132025708604"]));
                if ($amount >= 100000000) { //1億以上は報告チャンネルにも
                    ReefEdgePlugin::$socketClient->send(new SocketData("discord-message", ["message" => $p->getName() . " => " . $target->getName() . " : " . MoneyService::moneyFormat($amount),
                        "channelID" => "1081257724202983444"]));
                }
            });
        }), new ClosureButton("戻る", null, function () use ($p, $house, $pool, $targetName): void {
            self::sendForm($p, $house, $pool, $targetName);
        })))->setTitle("MoneyTransfer -> Confirm")->setText("$targetName さんに" . MoneyService::moneyFormat($amount) . "円送金しますか?");
        $p->sendForm($form);
    }
}
