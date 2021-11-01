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

namespace ree_jp\coral_reef\form;

use bbo51dog\bboform\element\ClosureButton;
use bbo51dog\bboform\element\Input;
use bbo51dog\bboform\element\Label;
use bbo51dog\bboform\form\ClosureCustomForm;
use bbo51dog\bboform\form\CustomForm;
use bbo51dog\bboform\form\ModalForm;
use bbo51dog\bboform\form\SimpleForm;
use pocketmine\Player;
use pocketmine\Server;
use ree_jp\coral_reef\account\AccountManager;

class PartyForm
{
    static array $members = [];

    static function partyForm(string $xuid): SimpleForm
    {
        $buttons = [];
        $members = [];
        $form = (new SimpleForm())
            ->setTitle("Menu -> Party")
            ->setText("パーティーメンバーを追加するか削除したいメンバーを選択してください");
        if (isset(self::$members[$xuid])) {
            $members = self::$members[$xuid];
            foreach ($members as $member) {
                $name = AccountManager::getUserName($member);
                $form->addElement(
                    new ClosureButton(
                        $name,
                        null,
                        function (Player $p, ClosureButton $button) use ($name, $member) {
                            $p->sendForm(
                                (new ModalForm(
                                    new ClosureButton(
                                        "はい",
                                        null,
                                        function (Player $p, ClosureButton $button) use ($name, $member) {
                                            if (self::isParty($p->getXuid(), $member)) {
                                                array_splice(self::$members[$p->getXuid()], $member);
                                                $p->sendMessage($name . 'さんをパーティーから削除しました');
                                            } else $p->sendMessage('エラーが発生しました');
                                        }
                                    ),
                                    new ClosureButton(
                                        "いいえ",
                                        null,
                                        function (Player $p, ClosureButton $button) {
                                            $p->sendForm(self::partyForm($p->getXuid()));
                                        }
                                    )
                                ))
                                    ->setTitle("Party -> Delete")
                                    ->setText("本当に$name さんをパーティーから削除しますか?\nいつでもパーティーに再参加させることができます")
                            );
                        }
                    )
                );
            }
        }
        $form->addElement(
            new ClosureButton(
                "メンバーを追加する",
                null,
                function (Player $p, ClosureButton $button) {
                    $p->sendForm(self::partyAddForm($p->getXuid()));
                }
            )
        );
        return $form;
    }

    static function isParty(string $party, string $xuid): bool
    {
        return isset(self::$members[$party]) && isset(self::$members[$party][$xuid]);
    }

    static function partyAddForm(): CustomForm
    {
        $memberNameInput = new Input('追加したいメンバーの名前を入力してください', '名前');
        return (new ClosureCustomForm(
            function (Player $p, ClosureCustomForm $form) use ($memberNameInput) {
                $member = Server::getInstance()->getPlayer($memberNameInput->getValue());
                if ($member instanceof Player) {
                    if ($member->getName() === $p->getName()) {
                        $p->sendMessage("パーティーに自分を追加することはできません");
                    } else {
                        $confirm = (new ModalForm(
                            new ClosureButton(
                                "はい",
                                null,
                                function (Player $p, ClosureButton $button) use ($member) {
                                    self::$members[$p->getXuid()][] = $member->getXuid();
                                    $p->sendMessage($member->getName() . 'さんをパーティーに追加しました');
                                    $member->sendMessage($p->getName() . 'さんのパーティーに追加されました');
                                }
                            ),
                            new ClosureButton(
                                "いいえ",
                                null,
                                function (Player $p, ClosureButton $button) use ($member) {
                                    $p->sendForm(self::partyAddForm());
                                }
                            )
                        ))
                            ->setTitle("PartyAdd -> Confirm")
                            ->setText($member->getName() . "さんをパーティーに追加しますか?");
                        $p->sendForm($confirm);
                    }

                } else {
                    $p->sendMessage($memberNameInput->getValue() . "さんはサーバーにいないためパーティーに追加出来ませんでした");
                }
            }
        ))
            ->setTitle("Party -> Add")
            ->addElements(
                new Label("パーティーメンバーを追加します"),
                $memberNameInput
            );
    }
}
