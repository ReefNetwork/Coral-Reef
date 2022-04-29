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

use Closure;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use pocketmine\world\Position;
use ree_jp\coral_reef\form\shop\data\DataShopDetailForm;
use ree_jp\coral_reef\form\shop\item\ItemShopDetailForm;
use ree_jp\coral_reef\form\shop\ShopManageForm;
use ree_jp\coral_reef\gatya\GatyaManager;
use ree_jp\coral_reef\money\MoneyService;
use ree_jp\coral_reef\shop\data\DataShop;
use ree_jp\coral_reef\shop\item\ItemShop;
use ree_jp\coral_reef\sql\SQLConst;
use ree_jp\coral_reef\sql\SQLRepository;

class ShopService
{
    static function showShop(SQLRepository $repo, Player $p, ShopStore $store, Position $pos): void
    {
        if ($p->isCreative() && $p->isSneaking()) {
            ShopManageForm::sendForm($p, $store, $pos);
            return;
        }

        $shop = $store->findShop($pos);
        if ($shop instanceof ItemShop) ItemShopDetailForm::sendForm($repo, $store, $p, $shop);
        if ($shop instanceof DataShop) DataShopDetailForm::sendForm($repo, $store, $p, $shop);
    }


    static function createKey(Position $pos): string
    {
        return $pos->getWorld()->getFolderName() . ":" . $pos->getX() . ":" . $pos->getY() . ":" . $pos->getZ();
    }

    static function pay(SQLRepository $repo, ShopStore $store, Shop $shop, string $xuid, int $count, bool $isBuy, Closure $func, Closure $failure): void
    {
        if ($count <= 0) {
            $failure();
            return;
        }

        $value = $shop->payment["amount"] * $count;
        switch ($shop->payment["type"]) {
            case "money":
                if ($isBuy) {
                    MoneyService::getMoney($repo, $xuid, function (int $money) use ($count, $store, $shop, $repo, $xuid, $func, $failure, $value): void {
                        if ($value <= $money && $shop->addDayLimitCounter($store, $xuid, $count)) {
                            MoneyService::reduceMoney($repo, $xuid, $value);
                            $func();
                        } else {
                            $failure();
                        }
                    });
                } else {
                    if ($shop->addDayLimitCounter($store, $xuid, $count)) {
                        MoneyService::addMoney($repo, $xuid, $value);
                        $func();
                    } else {
                        $failure();
                    }
                }
                break;

            case "normal_tickets":
                if ($isBuy) {
                    $repo->getValue($xuid, SQLConst::TYPE_TICKETS, SQLConst::TICKETS_NORMAL,
                        function (array $rows) use ($count, $store, $shop, $repo, $xuid, $func, $failure, $value): void {
                            $row = array_shift($rows);

                            if (isset($row['value']) && ($value <= intval($row['value'])) && $shop->addDayLimitCounter($store, $xuid, $count)) {
                                GatyaManager::addTicket($repo, $xuid, SQLConst::TICKETS_NORMAL, -$value, $func);
                            } else {
                                $failure();
                            }
                        }
                    );
                } else {
                    if ($shop->addDayLimitCounter($store, $xuid, $count)) {
                        GatyaManager::addTicket($repo, $xuid, SQLConst::TICKETS_NORMAL, $value, $func);
                    } else {
                        $failure();
                    }
                }
                break;

            default:
                $failure();
                break;
        }
    }


    static function replaceOrderType(string $text): string
    {
        $text = str_replace("buy", TextFormat::GREEN . "購入" . TextFormat::RESET, $text);
        return str_replace("sell", TextFormat::RED . "売却" . TextFormat::RESET, $text);
    }

    static function replacePaymentType(string $text): string
    {
        $text = str_replace("money", TextFormat::GOLD . "お金: " . TextFormat::RESET, $text);
        return str_replace("normal_tickets", TextFormat::BLUE . "ガチャチケット: " . TextFormat::RESET, $text);
    }
}