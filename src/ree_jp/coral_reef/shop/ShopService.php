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

use Closure;
use pocketmine\player\Player;
use pocketmine\world\Position;
use ree_jp\coral_reef\account\GiftData;
use ree_jp\coral_reef\account\GiftService;
use ree_jp\coral_reef\form\shop\ShopDetailForm;
use ree_jp\coral_reef\form\shop\ShopManageForm;
use ree_jp\coral_reef\gatya\GatyaManager;
use ree_jp\coral_reef\money\MoneyService;
use ree_jp\coral_reef\sql\SQLConst;
use ree_jp\coral_reef\sql\SQLRepository;

class ShopService
{
    static function showShop(SQLRepository $repo, Player $p, ShopStore $store, Position $pos): void
    {
        if ($pos->getWorld()->getFolderName() !== "lobby") return;

        if ($p->isCreative()) {
            ShopManageForm::sendForm($p, $store, $pos);
            return;
        }

        $shop = $store->findShop($pos);
        if (!is_null($shop)) {
            ShopDetailForm::sendForm($repo, $p, $shop);
        }
    }

    static function buy(SQLRepository $repo, Shop $shop, Player $p, int $count): void
    {
        $xuid = $p->getXuid();
        self::pay($repo, $shop, $xuid, $count, true, function () use ($repo, $xuid, $p, $count): void {
            $items = $this->getItems();
            $gifts = [];
            while ($count > 0) {
                foreach ($items as $item) {
                    if ($p->getInventory()->canAddItem($item)) {
                        $p->getInventory()->addItem($item);
                    } else $gifts[] = $item;
                }
                $count--;
            }
            if (!empty($gifts)) {
                GiftService::addGift($repo, $xuid, new GiftData(0, "ショップで購入したアイテムです", time() + (7 * 24 * 60 * 60), $gifts),
                    null, null);
                $p->sendMessage("アイテムの一部がインベントリに入らなかったためギフトに送信しました\n1週間以内に受け取ってください");
            }
            $p->sendMessage("購入しました");
        }, function () use ($p): void {
            $p->sendMessage("購入できませんでした");
        });
    }

    static function sell(SQLRepository $repo, Shop $shop, Player $p, int $count): void
    {
        foreach ($shop->getItems() as $item) {
            $item = $item->setCount($item->getCount() * $count);
            if (!$p->getInventory()->contains($item)) {
                $p->sendMessage("所持してるアイテムが足りなかったため売却できませんでした");
                return;
            }
        }
        self::pay($repo, $shop, $p->getXuid(), $count, false, function () use ($p, $count): void {
            foreach ($this->getItems() as $item) {
                $item = $item->setCount($item->getCount() * $count);
                $p->getInventory()->removeItem($item);
            }
            $p->sendMessage("売却しました");
        }, function () use ($p): void {
            $p->sendMessage("売却できませんでした");
        });
    }

    static function pay(SQLRepository $repo, Shop $shop, string $xuid, int $count, bool $isBuy, Closure $func, Closure $failure): void
    {
        $value = $shop->payment["amount"] * $count;
        switch ($shop->payment["type"]) {
            case "money":
                if ($isBuy) {
                    MoneyService::getMoney($repo, $xuid, function (int $money) use ($repo, $xuid, $func, $failure, $value): void {
                        if ($value <= $money) {
                            MoneyService::reduceMoney($repo, $xuid, $value);
                            $func();
                        } else {
                            $failure();
                        }
                    });
                } else {
                    MoneyService::addMoney($repo, $xuid, $value);
                    $func();
                }
                break;

            case "normal_tickets":
                if ($isBuy) {
                    $repo->getValue($xuid, SQLConst::TYPE_TICKETS, SQLConst::TICKETS_NORMAL,
                        function (array $rows) use ($repo, $xuid, $func, $failure, $value): void {
                            $row = array_shift($rows);
                            if (isset($row['value']) && ($value <= intval($row['value']))) {
                                GatyaManager::addTicket($repo, $xuid, SQLConst::TICKETS_NORMAL, -$value, $func);
                            } else {
                                $failure();
                            }
                        }
                    );
                } else {
                    GatyaManager::addTicket($repo, $xuid, SQLConst::TICKETS_NORMAL, $value, $func);
                }
                break;

            default:
                $failure();
                break;
        }
    }
}
