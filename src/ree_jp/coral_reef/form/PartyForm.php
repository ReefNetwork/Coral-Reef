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

use Frago9876543210\EasyForms\elements\Button;
use Frago9876543210\EasyForms\elements\Input;
use Frago9876543210\EasyForms\elements\Label;
use Frago9876543210\EasyForms\forms\CustomForm;
use Frago9876543210\EasyForms\forms\CustomFormResponse;
use Frago9876543210\EasyForms\forms\MenuForm;
use Frago9876543210\EasyForms\forms\ModalForm;
use pocketmine\Player;
use pocketmine\Server;
use ree_jp\coral_reef\account\AccountManager;

class PartyForm
{
    static array $members = [];

    static function partyForm(string $xuid): MenuForm
    {
        $buttons = [];
        $members = [];

        if (isset(self::$members[$xuid])) {
            $members = self::$members[$xuid];
            foreach ($members as $member) {
                $button = new Button(AccountManager::getUserName($member));
                array_push($buttons, $button);
            }
        }
        $createValue = array_push($buttons, new Button('メンバーを追加する')) - 1;
        return new MenuForm('Menu -> Party', 'パーティーメンバーを追加するか削除したいメンバーを選択してください', $buttons,
            function (Player $p, Button $button) use ($members, $createValue): void {
                if (isset($members[$button->getValue()])) {
                    $member = $members[$button->getValue()];
                    $name = AccountManager::getUserName($member);
                    $p->sendForm(new ModalForm('Party -> Delete', "本当に$name さんをパーティーから削除しますか?\nいつでもパーティーに再参加させることができます",
                        function (Player $p, bool $result) use ($member, $name): void {
                            $xuid = $p->getXuid();
                            if ($result) {
                                if (self::isParty($xuid, $member)) {
                                    array_splice(self::$members[$xuid], $member);
                                    $p->sendMessage($name . 'さんをパーティーから削除しました');
                                } else $p->sendMessage('エラーが発生しました');
                            } else $p->sendForm(self::partyForm($xuid));
                        }));
                } elseif ($button->getValue() === $createValue) {
                    $p->sendForm(self::partyAddForm());
                } else $p->sendMessage('エラーが発生しました');
            });
    }

    static function isParty(string $party, string $xuid): bool
    {
        return isset(self::$members[$party]) && isset(self::$members[$party][$xuid]);
    }

    static function partyAddForm(): CustomForm
    {
        return new CustomForm('Party -> Add', [
            new Label("パーティーメンバーを追加します"),
            new Input('追加したメンバーの名前を入力してください', '名前')
        ], function (Player $p, CustomFormResponse $result): void {
            $input = mb_strtolower($result->getInput()->getValue());
            $member = Server::getInstance()->getPlayer($input);
            if ($member instanceof Player) {
                $p->sendForm(new ModalForm('PartyAdd -> Confirm', $member->getName() . 'さんをパーティーに追加しますか?',
                    function (Player $p, bool $result) use ($member): void {
                        if ($result) {
                            self::$members[$p->getXuid()][] = $member->getXuid();
                            $p->sendMessage($member->getName() . 'さんをパーティーに追加しました');
                            $member->sendMessage($p->getName() . 'さんのパーティーに追加されました');
                        } else $p->sendForm(self::partyAddForm());
                    }));
            } else {
                $p->sendMessage($input . "さんはサーバーにいないためパーティーに追加出来ませんでした");
            }
        });
    }
}
