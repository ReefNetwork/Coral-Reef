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
use pocketmine\item\Item;
use pocketmine\player\Player;
use pocketmine\world\Position;
use ree_jp\coral_reef\form\shop\ShopDetailForm;
use ree_jp\coral_reef\form\shop\ShopManageForm;
use ree_jp\coral_reef\gatya\GatyaManager;
use ree_jp\coral_reef\money\MoneyService;
use ree_jp\coral_reef\sql\SQLConst;
use ree_jp\coral_reef\sql\SQLRepository;
use ree_jp\stackstorage\api\StackStorageAPI;
use Throwable;

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
        if (!is_null($shop)) {
            ShopDetailForm::sendForm($repo, $store, $p, $shop);
        }
    }

    static function buy(SQLRepository $repo, ShopStore $store, Shop $shop, Player $p, int $count, bool $isStorage): void
    {
        $xuid = $p->getXuid();
        self::pay($repo, $store, $shop, $xuid, $count, true, function () use ($isStorage, $shop, $repo, $xuid, $p, $count): void {
            if (!$p->isOnline()) return;

            $items = $shop->getItems();
            if (is_null($items)) {
                $p->sendMessage("エラーが発生しました");
                return;
            }
            $isNoInv = true;
            while ($count > 0) {
                foreach ($items as $item) {
                    if ($p->getInventory()->canAddItem($item) && !$isStorage) {
                        $p->getInventory()->addItem($item);
                    } else {
                        try {
                            \ree_jp\stackStorage\api\StackStorageAPI::$instance->add($p->getXuid(), $item);
                            $isNoInv = false | $isStorage;
                        } catch (Throwable) {
                            $p->sendMessage("ストレージにアクセスできなかったためアイテムをドロップしました");
                            $p->dropItem($item);
                        }
                    }
                }
                $count--;
            }
            if (!$isNoInv) {
                $p->sendMessage("アイテムの一部がインベントリに入らなかったためストレージに収納されました");
            }
            $p->sendMessage("購入しました");
        }, function () use ($p): void {
            if (!$p->isOnline()) return;

            $p->sendMessage("購入できませんでした");
        });
    }

    static function sell(SQLRepository $repo, ShopStore $store, Shop $shop, Player $p, int $count, bool $isDirectSell, array $storage = []): void
    {
        if ($isDirectSell) {
            StackStorageAPI::$instance->getAllItems($p->getXuid(), function (array $storageItems) use ($count, $p, $shop, $repo, $store): void {
                self::sell($repo, $store, $shop, $p, $count, false, $storageItems);
            }, null);
            return;
        }
        $haveItems = array_merge($p->getInventory()->getContents(), $storage);
        foreach ($shop->getItems() as $item) {
            $item = $item->setCount($item->getCount() * $count);
            $itemRemainingCount = self::getCount($haveItems, $item);
            if ($itemRemainingCount <= 0) return;
        }
        self::pay($repo, $store, $shop, $p->getXuid(), $count, false, function () use ($shop, $p, $count): void {
            if (!$p->isOnline()) return;

            foreach ($shop->getItems() as $item) {
                $item = $item->setCount($item->getCount() * $count);
                $p->getInventory()->removeItem($item);
            }
            $p->sendMessage("売却しました");
        }, function () use ($p): void {
            if (!$p->isOnline()) return;

            $p->sendMessage("売却できませんでした");
        });
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

    static function createKey(Position $pos): string
    {
        return $pos->getWorld()->getFolderName() . ":" . $pos->getX() . ":" . $pos->getY() . ":" . $pos->getZ();
    }

    /**
     * @param Item[] $items
     */
    private static function getCount(array $items, Item $item): int
    {
        $count = 0;
        foreach ($items as $i) {
            if ($item->equals($i)) {
                $count += $i->getCount();
            }
        }
        return $count;
    }
}
