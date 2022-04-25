<?php
/*
 *  CCCCC                        lll RRRRRR                 fff
 * CC    C  oooo  rr rr    aa aa lll RR   RR   eee    eee  ff
 * CC      oo  oo rrr  r  aa aaa lll RRRRRR  ee   e ee   e ffff
 * CC    C oo  oo rr     aa  aaa lll RR  RR  eeeee  eeeee  ff
 *  CCCCC   oooo  rr      aaa aa lll RR   RR  eeeee  eeeee ff
 *
 * Copyright (c) 2022. Ree-jp(https://ree-jp.net)
 */

namespace ree_jp\coral_reef\shop;

use pocketmine\player\Player;
use pocketmine\world\Position;
use ree_jp\coral_reef\form\shop\item\ItemShopDetailForm;
use ree_jp\coral_reef\form\shop\item\ShopManageForm;
use ree_jp\coral_reef\shop\item\ItemShop;
use ree_jp\coral_reef\sql\SQLRepository;

class ShopService
{
    static function showShop(SQLRepository $repo, Player $p, ShopStore $store, Position $pos): void
    {
        if ($pos->getWorld()->getFolderName() !== "lobby") return;

        if ($p->isCreative() && $p->isSneaking()) {
            ShopManageForm::sendForm($p, $store, $pos);
            return;
        }

        $shop = $store->findShop($pos);
        if ($shop instanceof ItemShop) ItemShopDetailForm::sendForm($repo, $store, $p, $shop);
    }
}