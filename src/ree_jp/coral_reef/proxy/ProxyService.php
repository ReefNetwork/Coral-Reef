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

namespace ree_jp\coral_reef\proxy;

use pocketmine\network\mcpe\protocol\TransferPacket;
use pocketmine\player\Player;
use pocketmine\scheduler\ClosureTask;
use ree_jp\coral_reef\account\AccountStore;
use ree_jp\coral_reef\CoralReefPlugin;
use ree_jp\coral_reef\sql\SQLRepository;

class ProxyService
{
    static function transferServerWithSave(SQLRepository $repo, AccountStore $store, Player $p, string $server): void
    {
        $store->setValue($p->getXuid(), "wait_action");
        $p->setImmobile();
        $p->sendMessage("サーバー移動の準備中です...");

        $user = $store->getUser($p->getXuid());
        $user->save($repo, function () use ($store, $server, $p) {
            $store->setValue($p->getXuid(), "save_xp");
            self::transferServer($store, $p, $server, true);
        }, function () use ($store, $server, $p) {
            $store->setValue($p->getXuid(), "save_skill");
            self::transferServer($store, $p, $server, true);
        }, function () use ($store, $server, $p) {
            $store->setValue($p->getXuid(), "save_quest");
            self::transferServer($store, $p, $server, true);
        });
    }

    static private function transferServer(AccountStore $store, Player $p, string $address, bool $isCheckSafe): void
    {
        $xuid = $p->getXuid();
        // isCheckSafeの場合すべてセーブされたか確認する
        if (!$isCheckSafe || $store->hasValue($xuid, "save_xp") &&
            $store->hasValue($xuid, "save_skill") && $store->hasValue($xuid, "save_quest")) {
            if ($isCheckSafe) {
                $store->setValue($p->getXuid(), "save_xp", 0);
                $store->setValue($p->getXuid(), "save_skill", 0);
                $store->setValue($p->getXuid(), "save_quest", 0);
            }
            $pk = new TransferPacket();
            $pk->address = $address;
            $p->sendMessage("サーバーを移動しています");
            $p->getNetworkSession()->sendDataPacket($pk);
            CoralReefPlugin::$plugin->getScheduler()->scheduleDelayedTask(new ClosureTask(
                function () use ($store, $p): void {
                    if ($p->isClosed()) return;
                    $xuid = $p->getXuid();
                    $store->setValue($xuid, "transfer_server", 0);
                    $store->setValue($xuid, "wait_action", 0);
                    $p->setImmobile(false);
                    $p->sendMessage("サーバーを移動出来ませんでした");
                }
            ), 20 * 3);
        }
    }
}
