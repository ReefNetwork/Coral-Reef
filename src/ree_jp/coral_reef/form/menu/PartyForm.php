<?php
/*
 *  CCCCC                        lll RRRRRR                 fff
 * CC    C  oooo  rr rr    aa aa lll RR   RR   eee    eee  ff
 * CC      oo  oo rrr  r  aa aaa lll RRRRRR  ee   e ee   e ffff
 * CC    C oo  oo rr     aa  aaa lll RR  RR  eeeee  eeeee  ff
 *  CCCCC   oooo  rr      aaa aa lll RR   RR  eeeee  eeeee ff
 *
 * Copyright (c) 2021-2021. Ree-jp(https://ree-jp.net)
 */

namespace ree_jp\coral_reef\form\menu;

use bbo51dog\bboform\element\ClosureButton;
use bbo51dog\bboform\element\Input;
use bbo51dog\bboform\element\Label;
use bbo51dog\bboform\form\ClosureCustomForm;
use bbo51dog\bboform\form\ModalForm;
use bbo51dog\bboform\form\SimpleForm;
use pocketmine\player\Player;
use pocketmine\Server;
use ree_jp\coral_reef\account\AccountStore;

class PartyForm
{
    static array $members = [];

    static function sendPartyForm(AccountStore $store, Player $p): void
    {
        $xuid = $p->getXuid();
        $form = (new SimpleForm())
            ->setTitle("Menu -> Party")
            ->setText("パーティーメンバーを追加するか削除したいメンバーを選択してください\nパーティーメンバーになると土地保護を一緒に掘れるようになります\n" .
                "サーバーが再起動されるとリセットされます");

        if (isset(self::$members[$xuid])) {
            foreach (self::$members[$xuid] as $member) {
                $name = $store->getUserName($member);
                $form->addElement(
                    new ClosureButton(
                        $name, null,
                        function (Player $p) use ($store, $name, $member) {
                            self::sendPartyDeleteConfirmForm($store, $p, $name, $member);
                        }
                    )
                );
            }
        }
        $form->addElement(
            new ClosureButton(
                "メンバーを追加する", null,
                function (Player $p) {
                    self::sendPartyAddForm($p);
                }
            )
        );
        $p->sendForm($form);
    }

    private static function sendPartyDeleteConfirmForm(AccountStore $store, Player $p, string $name, string $xuid): void
    {
        $form = new ModalForm(
            new ClosureButton(
                "はい", null,
                function (Player $p) use ($name, $xuid) {
                    if (self::isParty($p->getXuid(), $xuid)) {
                        array_splice(self::$members[$p->getXuid()], $xuid);
                        $p->sendMessage($name . 'さんをパーティーから削除しました');
                    } else $p->sendMessage('エラーが発生しました');
                }
            ),
            new ClosureButton(
                "いいえ", null, function (Player $p) use ($store) {
                self::sendPartyForm($store, $p);
            })
        );
        $form->setTitle("Party -> Delete")
            ->setText("本当に$name さんをパーティーから削除しますか?\nいつでもパーティーに再参加させることができます");
        $p->sendForm($form);
    }

    static function isParty(string $party, string $xuid): bool
    {
        return !empty(self::$members[$party]) && in_array($xuid, self::$members[$party]);
    }

    static function sendPartyAddForm(Player $p): void
    {
        $memberNameInput = new Input('追加したいメンバーの名前を入力してください', '名前');
        $form = new ClosureCustomForm(
            function (Player $p) use ($memberNameInput) {
                $member = Server::getInstance()->getPlayerByPrefix($memberNameInput->getValue());
                if ($member instanceof Player) {
                    if ($member->getName() === $p->getName()) {
                        $p->sendMessage("パーティーに自分を追加することはできません");
                    } else {
                        self::sendPartyAddConfirmForm($p, $member);
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

    private static function sendPartyAddConfirmForm(Player $p, Player $member): void
    {
        $form = new ModalForm(
            new ClosureButton(
                "はい", null,
                function (Player $p) use ($member) {
                    self::$members[$p->getXuid()][] = $member->getXuid();
                    $p->sendMessage($member->getName() . 'さんをパーティーに追加しました');
                    $member->sendMessage($p->getName() . 'さんのパーティーに追加されました');
                }
            ),
            new ClosureButton(
                "いいえ", null,
                function (Player $p) use ($member) {
                    self::sendPartyAddForm($p);
                }
            )
        );
        $form->setTitle("PartyAdd -> Confirm")->setText($member->getName() . "さんをパーティーに追加しますか?");
        $p->sendForm($form);
    }
}
