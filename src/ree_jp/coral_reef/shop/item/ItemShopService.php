<?php
/*
 *  CCCCC                        lll RRRRRR                 fff
 * CC    C  oooo  rr rr    aa aa lll RR   RR   eee    eee  ff
 * CC      oo  oo rrr  r  aa aaa lll RRRRRR  ee   e ee   e ffff
 * CC    C oo  oo rr     aa  aaa lll RR  RR  eeeee  eeeee  ff
 *  CCCCC   oooo  rr      aaa aa lll RR   RR  eeeee  eeeee ff
 *
 * Copyright (c) 2021-2022. Ree-jp(https://ree-jp.net)
 */

namespace ree_jp\coral_reef\shop\item;

use pocketmine\item\Item;
use pocketmine\player\Player;
use ree_jp\coral_reef\shop\ShopService;
use ree_jp\coral_reef\shop\ShopStore;
use ree_jp\coral_reef\sql\mysql\SQLRepository;
use ree_jp\stackstorage\api\StackStorageAPI;
use Throwable;

class ItemShopService
{
    static function buy(SQLRepository $repo, ShopStore $store, ItemShop $shop, Player $p, int $count, bool $isStorage): void
    {
        $xuid = $p->getXuid();
        ShopService::pay($repo, $store, $shop, $xuid, $count, true, function () use ($isStorage, $shop, $repo, $xuid, $p, $count): void {
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

    static function sell(SQLRepository $repo, ShopStore $store, ItemShop $shop, Player $p, int $count, bool $isDirectSell, ?array $storage = null): void
    {
        if ($isDirectSell && $storage == null) {
            StackStorageAPI::$instance->getAllItems($p->getXuid(), function (array $storageItems) use ($count, $p, $shop, $repo, $store): void {
                self::sell($repo, $store, $shop, $p, $count, true, $storageItems);
            }, null);
            return;
        }
        /** @var Item[] */
        $removeInv = [];
        /** @var Item[] */
        $removeStorage = [];
        foreach ($shop->getItems() as $item) {
            $item = $item->setCount($item->getCount() * $count);
            $invCount = self::getCount($p->getInventory()->all($item));
            $itemRemainingCount = $item->getCount() - $invCount;

            if ($itemRemainingCount <= 0) {
                $removeInv[] = clone $item;
                continue;
            } else {
                $removeInv[] = (clone $item)->setCount($invCount);
            }

            if ($storage != null) {
                $storageCount = self::getCount($storage, $item);
                if ($itemRemainingCount <= $storageCount) {
                    $removeStorage[] = (clone $item)->setCount($itemRemainingCount);
                    continue;
                }
            }
            $p->sendMessage("アイテムが足りません");
            return;
        }
        ShopService::pay($repo, $store, $shop, $p->getXuid(), $count, false, function () use ($removeStorage, $removeInv, $p, $count): void {
            if (!$p->isOnline()) return;

            foreach ($removeInv as $item) {
                $p->getInventory()->removeItem($item);
            }
            foreach ($removeStorage as $item) {
                StackStorageAPI::$instance->remove($p->getXuid(), $item);
            }
            $p->sendMessage("売却しました");
        }, function () use ($p): void {
            if (!$p->isOnline()) return;

            $p->sendMessage("売却できませんでした");
        });
    }

    /**
     * @param Item[] $items
     */
    private static function getCount(array $items, ?Item $item = null): int
    {
        $count = 0;
        foreach ($items as $i) {
            if ($item != null && !$item->equals($i)) continue;
            $count += $i->getCount();
        }
        return $count;
    }
}
