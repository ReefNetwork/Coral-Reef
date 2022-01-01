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
use bbo51dog\bboform\form\SimpleForm;
use pocketmine\player\Player;
use ree_jp\coral_reef\account\AccountStore;
use ree_jp\coral_reef\proxy\ProxyManager;
use ree_jp\coral_reef\sql\SQLManager;

class ServerSelectForm
{
    const SERVERS = ["lobby", "整地1", '整地2'];

    static function sendForm(SQLManager $repo, AccountStore $store, Player $p): void
    {
        $form = (new SimpleForm())
            ->setTitle("Menu -> Server")
            ->setText("サーバーを移動できます");
        foreach (self::SERVERS as $server) {
            $form->addElement(new ClosureButton(
                $server, null,
                function (Player $p) use ($store, $repo, $server) {
                    ProxyManager::transferServerWithSave($repo, $store, $p, $server);
                }
            ));
        }
        $p->sendForm($form);
    }
}
