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

use alemiz\sga\StarGateAtlantis;
use bbo51dog\bboform\element\ClosureButton;
use bbo51dog\bboform\form\SimpleForm;
use pocketmine\Player;
use ree_jp\coral_reef\proxy\packet\ProxyCommandExecutePacket;
use ree_jp\coral_reef\proxy\ProxyManager;

class ServerSelectForm
{
    const SERVERS = ["lobby", "整地1", '整地2'];

    static function sendServerSelectForm(Player $p): void
    {
        $form = (new SimpleForm())
            ->setTitle("Menu -> Server")
            ->setText("サーバーを移動できます");
        foreach (self::SERVERS as $server) {
            $form->addElement(new ClosureButton(
                $server,
                null,
                function (Player $p, ClosureButton $button) use ($server) {
                    if ($server === "lobby") {
                        $pk = new ProxyCommandExecutePacket();
                        $pk->playerName = $p->getName();
                        $pk->command = "lobby";
                        StarGateAtlantis::getInstance()->getDefaultClient()->sendPacket($pk);
                        return;
                    }
                    ProxyManager::transferServerWithSave($p, $server);
                }
            ));
        }
        $p->sendForm($form);
    }
}
