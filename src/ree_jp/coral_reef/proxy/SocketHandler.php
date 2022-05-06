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
use ree_jp\coral_reef\sql\mysql\SQLRepository;

class SocketHandler
{
    static function register(\ree_jp\reef_edge\socket\SocketHandler $handler, SQLRepository $repo, AccountStore $accountStore): void
    {
        $handler->registerHandler("transfer-request", function (array $data) use ($accountStore, $repo): void {
            if (isset($data["player"]) && isset($data["server"])) {
                $p = Server::getInstance()->getPlayerByUUID(Uuid::fromString($data["player"]));
                if ($p instanceof Player) {
                    ProxyService::transferServerWithSave($repo, $accountStore, $p, $data["server"]);
                }
            }
        });
    }
}