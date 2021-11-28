<?php
/*
 *  CCCCC                        lll RRRRRR                 fff
 * CC    C  oooo  rr rr    aa aa lll RR   RR   eee    eee  ff
 * CC      oo  oo rrr  r  aa aaa lll RRRRRR  ee   e ee   e ffff
 * CC    C oo  oo rr     aa  aaa lll RR  RR  eeeee  eeeee  ff
 *  CCCCC   oooo  rr      aaa aa lll RR   RR  eeeee  eeeee ff
 *
 * Copyright (c) 2021. Ree-jp(https://ree-jp.net)
 */

namespace ree_jp\coral_reef\shop;

use pocketmine\level\Position;
use pocketmine\Player;
use ree_jp\coral_reef\shop\form\ShopDetailForm;
use ree_jp\coral_reef\shop\form\ShopManageForm;

class ShopService
{
    static function showShop(Player $p, ShopStore $store, Position $pos): void
    {
        if ($pos->getLevel()?->getFolderName() !== "lobby") return;

        if ($p->isCreative()) {
            ShopManageForm::sendForm($p, $store, $pos);
            return;
        }

        $shop = $store->findShop($p);
        if (!is_null($shop)) {
            ShopDetailForm::sendForm($p, $shop);
        }
    }
}
