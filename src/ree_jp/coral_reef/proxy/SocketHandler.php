<?php
/*
 *  CCCCC                        lll RRRRRR                 fff
 * CC    C  oooo  rr rr    aa aa lll RR   RR   eee    eee  ff
 * CC      oo  oo rrr  r  aa aaa lll RRRRRR  ee   e ee   e ffff
 * CC    C oo  oo rr     aa  aaa lll RR  RR  eeeee  eeeee  ff
 *  CCCCC   oooo  rr      aaa aa lll RR   RR  eeeee  eeeee ff
 *
 * Copyright (c) 2022-2022. Ree-jp(https://ree-jp.net)
 */

namespace ree_jp\coral_reef\proxy;

use pocketmine\player\Player;
use pocketmine\Server;
use Ramsey\Uuid\Uuid;
use ree_jp\coral_reef\account\AccountStore;
use ree_jp\coral_reef\account\BetweenRanking;
use ree_jp\coral_reef\session\SessionService;
use ree_jp\coral_reef\sql\RepositoryPool;
use ree_jp\coral_reef\StoreHouse;

class SocketHandler
{
    static string $day;

    static function register(\ree_jp\reef_edge\socket\SocketHandler $handler, RepositoryPool $pool, StoreHouse $store): void
    {
        /** @var AccountStore $accountStore */
        $accountStore = $store->get(AccountStore::class);


        $handler->registerHandler("transfer-request", function (array $data) use ($accountStore, $pool): void {
            $p = Server::getInstance()->getPlayerByUUID(Uuid::fromString($data["player"]));
            if ($p instanceof Player) {
                ProxyService::transferServerWithSave($pool, $accountStore, $p, $data["server"]);
            }
        });

        self::$day = date("d");
        $betweenRanking = new BetweenRanking($pool, $store);

        $handler->registerHandler("timer", function (array $data) use ($betweenRanking, $pool, $store): void {
            $nowDay = date("d", strtotime($data["time"]));

            $betweenRanking->showRanking();

            if ($nowDay === self::$day) return;
            self::$day = $nowDay;

            foreach (Server::getInstance()->getOnlinePlayers() as $p) {
                SessionService::reCreateSession($pool, $store, $p->getXuid());
            }
        });
    }
}
