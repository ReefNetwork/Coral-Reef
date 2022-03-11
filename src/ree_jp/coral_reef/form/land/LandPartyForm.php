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

namespace ree_jp\coral_reef\form\land;

use bbo51dog\bboform\element\ClosureButton;
use bbo51dog\bboform\element\Input;
use bbo51dog\bboform\element\Label;
use bbo51dog\bboform\form\ClosureCustomForm;
use bbo51dog\bboform\form\ModalForm;
use bbo51dog\bboform\form\SimpleForm;
use pocketmine\player\Player;
use pocketmine\Server;
use ree_jp\coral_reef\account\AccountStore;
use ree_jp\coral_reef\land\LandStore;

class LandPartyForm
{
    static function sendForm(AccountStore $accountStore, LandStore $landStore, Player $p): void
    {
        $form = (new SimpleForm())
            ->setTitle("Land -> Party")
            ->setText("パーティーメンバーを追加するか削除したいメンバーを選択してください\nパーティーメンバーになると土地保護を一緒に掘れるようになります\n" .
                "サーバーが再起動されるとリセットされます");

        foreach ($landStore->allPartyMember($p->getXuid()) as $member) {
            $name = $accountStore->getUserName($member);
            $form->addElement(
                new ClosureButton(
                    $name, null,
                    function (Player $p) use ($landStore, $accountStore, $name, $member) {
                        self::sendPartyDeleteConfirmForm($accountStore, $landStore, $p, $name, $member);
                    }
                )
            );
        }

        $form->addElement(
            new ClosureButton(
                "パーティーを追加する", null,
                function (Player $p) use ($landStore) {
                    self::sendPartyAddForm($landStore, $p);
                }
            )
        );
        $p->sendForm($form);
    }

    private static function sendPartyDeleteConfirmForm(AccountStore $accountStore, LandStore $landStore, Player $p, string $name, string $xuid): void
    {
        $form = new ModalForm(
            new ClosureButton(
                "はい", null,
                function (Player $p) use ($landStore, $name, $xuid) {
                    $landStore->deleteParty($p->getXuid(), $xuid);
                    $p->sendMessage($name . 'さんをパーティーから削除しました');
                }
            ),
            new ClosureButton(
                "いいえ", null, function (Player $p) use ($accountStore, $landStore) {
                self::sendForm($accountStore, $landStore, $p);
            })
        );
        $form->setTitle("Party -> Delete")
            ->setText("本当に$name さんをパーティーから削除しますか?\nいつでもパーティーに再参加させることができます");
        $p->sendForm($form);
    }

    static function sendPartyAddForm(LandStore $store, Player $p): void
    {
        $memberNameInput = new Input('追加したいパーティーの名前を入力してください', '名前');
        $form = new ClosureCustomForm(
            function (Player $p) use ($store, $memberNameInput) {
                $member = Server::getInstance()->getPlayerByPrefix($memberNameInput->getValue());
                if ($member instanceof Player) {
                    if ($member->getName() === $p->getName()) {
                        $p->sendMessage("パーティーに自分を追加することはできません");
                    } else {
                        self::sendPartyAddConfirmForm($store, $p, $member);
                    }
                } else {
                    $p->sendMessage($memberNameInput->getValue() . "さんはサーバーにいないためパーティーに追加出来ませんでした");
                }
            }
        );
        $form->setTitle("Party -> Add")
            ->addElements(new Label("パーティーメンバーを追加します"), $memberNameInput);
        $p->sendForm($form);
    }

    private static function sendPartyAddConfirmForm(LandStore $store, Player $p, Player $member): void
    {
        $form = new ModalForm(
            new ClosureButton(
                "はい", null,
                function (Player $p) use ($store, $member) {
                    $store->addParty($p->getXuid(), $member->getXuid());
                    $p->sendMessage($member->getName() . 'さんをパーティーに追加しました');
                    $member->sendMessage($p->getName() . 'さんのパーティーに追加されました');
                }
            ),
            new ClosureButton(
                "いいえ", null,
                function (Player $p) use ($store, $member) {
                    self::sendPartyAddForm($store, $p);
                }
            )
        );
        $form->setTitle("PartyAdd -> Confirm")->setText($member->getName() . "さんをパーティーに追加しますか?");
        $p->sendForm($form);
    }
}
