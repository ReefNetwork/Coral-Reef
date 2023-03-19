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
use bbo51dog\bboform\form\SimpleForm;
use pocketmine\player\Player;
use ree_jp\coral_reef\account\AccountStore;
use ree_jp\coral_reef\sql\RepositoryPool;
use ree_jp\coral_reef\StoreHouse;

class PlayerInfoForm
{
    static function sendForm(Player $p, Player $target, StoreHouse $house, RepositoryPool $pool): void
    {
        /** @var AccountStore $store */
        $store = $house->get(AccountStore::class);
        $targetData = $store->getUser($target->getXuid());
        $form = (new SimpleForm())->setTitle("{$target->getName()}")->setText("レベル: $targetData->level\n経験値: $targetData->experience")
            ->addElements(new ClosureButton("送金する", null, function () use ($house, $p, $target, $pool): void {
                MoneyForm::sendForm($p, $house, $pool, $target->getName());
            }));
        $p->sendForm($form);
    }
}
