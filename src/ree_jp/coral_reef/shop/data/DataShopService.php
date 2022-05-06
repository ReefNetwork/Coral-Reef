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

namespace ree_jp\coral_reef\shop\data;

use pocketmine\player\Player;
use ree_jp\coral_reef\shop\ShopService;
use ree_jp\coral_reef\shop\ShopStore;
use ree_jp\coral_reef\sql\mysql\SQLRepository;
use ree_jp\reef_edge\ReefEdgePlugin;
use ree_jp\reef_edge\socket\SocketData;

class DataShopService
{
    static function buy(SQLRepository $repo, ShopStore $store, DataShop $shop, Player $p, int $count): void
    {
        $xuid = $p->getXuid();
        $dataArray = ["xuid" => $xuid, "type" => $shop->data->type, "subType" => $shop->data->subtype, "item" => $shop->data->value];
        ReefEdgePlugin::$socketClient->send(new SocketData("item-count", $dataArray), function (array $result) use ($dataArray, $store, $xuid, $repo, $count, $shop, $p): void {
            if (!$p->isOnline()) return;
            if (!$result["isSuccess"] || !isset($result["count"])) {
                $p->sendMessage("エラーが発生しました");
                return;
            }
            $buyCount = $count * $shop->data->count;
            $afterCount = $result["count"] + $buyCount;
            if ($shop->haveLimit > 0 && $afterCount > $shop->haveLimit) {
                $p->sendMessage("所持制限を超えて購入することは出来ません");
                return;
            }
            ShopService::pay($repo, $store, $shop, $xuid, $count, true, function () use ($dataArray, $buyCount, $p): void {
                $dataArray["count"] = $buyCount;
                $dataArray["isNotDuplicate"] = false;
                ReefEdgePlugin::$socketClient->send(new SocketData("item-add", $dataArray), function (array $result) use ($p): void {
                    if (!$p->isOnline()) return;
                    if (!$result["isSuccess"]) {
                        $p->sendMessage("予期しないエラーが発生しました報告してください");
                        return;
                    }
                    $p->sendMessage("購入しました");
                });
            }, function () use ($p): void {
                if (!$p->isOnline()) return;

                $p->sendMessage("購入できませんでした");
            });
        });
    }

    static function sell(SQLRepository $repo, ShopStore $store, DataShop $shop, Player $p, int $count): void
    {
        $xuid = $p->getXuid();
        $dataArray = ["xuid" => $xuid, "type" => $shop->data->type, "subType" => $shop->data->subtype, "item" => $shop->data->value];
        ReefEdgePlugin::$socketClient->send(new SocketData("item-count", $dataArray), function (array $result) use ($dataArray, $store, $xuid, $repo, $count, $shop, $p): void {
            if (!$p->isOnline()) return;
            if (!$result["isSuccess"] || !isset($result["count"])) {
                $p->sendMessage("エラーが発生しました");
                return;
            }
            $sellCount = $count * $shop->data->count;
            $afterCount = $result["count"] - $sellCount;
            if ($shop->haveLimit > 0 && $afterCount < 0) {
                $p->sendMessage("所持数が足りません");
                return;
            }
            ShopService::pay($repo, $store, $shop, $p->getXuid(), $count, false, function () use ($dataArray, $sellCount, $p): void {
                $dataArray["count"] = -$sellCount;
                $dataArray["isNotDuplicate"] = false;
                ReefEdgePlugin::$socketClient->send(new SocketData("item-add", $dataArray), function (array $result) use ($p): void {
                    if (!$p->isOnline()) return;
                    if (!$result["isSuccess"]) {
                        $p->sendMessage("予期しないエラーが発生しました報告してください");
                        return;
                    }
                    $p->sendMessage("売却しました");
                });
            }, function () use ($p): void {
                if (!$p->isOnline()) return;

                $p->sendMessage("売却できませんでした");
            });
        });

    }
}