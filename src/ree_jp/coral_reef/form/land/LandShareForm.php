<?php
/*
 *  CCCCC                        lll RRRRRR                 fff
 * CC    C  oooo  rr rr    aa aa lll RR   RR   eee    eee  ff
 * CC      oo  oo rrr  r  aa aaa lll RRRRRR  ee   e ee   e ffff
 * CC    C oo  oo rr     aa  aaa lll RR  RR  eeeee  eeeee  ff
 *  CCCCC   oooo  rr      aaa aa lll RR   RR  eeeee  eeeee ff
 *
 * Copyright (c) 2022-2022. Ree-jp(https://ree-jp.net)
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
use ree_jp\coral_reef\land\LandData;
use ree_jp\coral_reef\land\LandService;
use ree_jp\coral_reef\sql\SQLRepository;

class LandShareForm
{
    static function sendForm(SQLRepository $repo, AccountStore $store, LandData $land, Player $p): void
    {
        $form = (new SimpleForm())
            ->setTitle("Land -> Share")
            ->setText("土地保護の共有をする人を追加するか削除したいメンバーを選択してください\n土地保護を一緒に掘れるようになります\n" .
                "サーバーが再起動しても解除されません");

        foreach ($land->members as $member) {
            $name = $store->getUserName($member);
            $form->addElement(
                new ClosureButton(
                    $name, null,
                    function (Player $p) use ($land, $repo, $store, $name, $member) {
                        self::sendMemberDeleteConfirmForm($repo, $store, $land, $p, $name, $member);
                    }
                )
            );
        }

        $form->addElement(
            new ClosureButton(
                "メンバーを追加する", null,
                function (Player $p) use ($repo, $store, $land) {
                    self::sendMemberAddForm($repo, $store, $land, $p);
                }
            )
        );
        $p->sendForm($form);
    }

    private static function sendMemberDeleteConfirmForm(SQLRepository $repo, AccountStore $store, LandData $land, Player $p, string $name, string $xuid): void
    {
        $form = new ModalForm(
            new ClosureButton(
                "はい", null,
                function (Player $p) use ($repo, $land, $xuid) {
                    LandService::deleteShareMember($repo, $land, $p, $xuid);
                }
            ),
            new ClosureButton(
                "いいえ", null, function (Player $p) use ($repo, $store, $land) {
                self::sendForm($repo, $store, $land, $p);
            })
        );
        $form->setTitle("Share -> Delete")
            ->setText("本当に$name さんを土地保護から削除しますか?\nいつでも土地保護に再参加させることができます");
        $p->sendForm($form);
    }

    static function sendMemberAddForm(SQLRepository $repo, AccountStore $store, LandData $land, Player $p): void
    {
        $memberNameInput = new Input("追加したいメンバーの名前を入力してください", "名前");
        $form = new ClosureCustomForm(
            function (Player $p) use ($repo, $land, $store, $memberNameInput) {
                $member = $store->getXuid($memberNameInput->getValue());
                if (!is_null($member)) {
                    if ($member === $p->getXuid()) {
                        $p->sendMessage("メンバーに自分を追加することはできません");
                    } else {
                        self::sendPartyAddConfirmForm($repo, $store, $land, $p, $memberNameInput->getValue(), $member);
                    }
                } else {
                    $p->sendMessage($memberNameInput->getValue() . "さんは見つかりませんでした");
                }
            }
        );
        $form->setTitle("Share -> Add")
            ->addElements(new Label("メンバーを追加します"), $memberNameInput);
        $p->sendForm($form);
    }

    private static function sendPartyAddConfirmForm(SQLRepository $repo, AccountStore $store, LandData $land, Player $p, string $name, string $xuid): void
    {
        $form = new ModalForm(
            new ClosureButton(
                "はい", null,
                function (Player $p) use ($name, $repo, $land, $xuid) {
                    LandService::addShareMember($repo, $land, $p, $xuid);
                    $partyMember = Server::getInstance()->getPlayerByPrefix($name);
                    if ($partyMember instanceof Player) $partyMember->sendMessage($p->getName() . "さんの土地保護($land->name)に参加しました");
                }
            ),
            new ClosureButton(
                "いいえ", null,
                function (Player $p) use ($repo, $land, $store) {
                    self::sendMemberAddForm($repo, $store, $land, $p);
                }
            )
        );
        $form->setTitle("PartyAdd -> Confirm")->setText($name . "さんをパーティーに追加しますか?");
        $p->sendForm($form);
    }
}
