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
use Frago9876543210\EasyForms\forms\MenuForm;
use pocketmine\Player;
use ree_jp\coral_reef\proxy\ProxyManager;

class ServerSelectForm
{
    const SERVERS = ["lobby", 'server_1', 'server_2', 'server_3'];

    static function sendServerSelectForm(Player $p): void
    {
        $buttons = [];
        foreach (self::SERVERS as $server) {
            $buttons[] = new Button($server);
        }
        $p->sendForm(new MenuForm('Menu -> Server', 'サーバーを移動できます', $buttons, function (Player $p, Button $button): void {
            ProxyManager::transferServerWithSave($p, $button->getText());
        }));
    }
}
