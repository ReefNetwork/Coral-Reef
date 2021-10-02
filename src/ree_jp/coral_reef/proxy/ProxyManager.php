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
use pocketmine\Player;
use pocketmine\scheduler\ClosureTask;
use pocketmine\Server;
use ree_jp\coral_reef\account\AccountManager;
use ree_jp\coral_reef\CoralReefPlugin;
use ree_jp\coral_reef\sql\SQLManager;

class ProxyManager
{
    static function transferServer(Player $p, string $server): void
    {
        AccountManager::setValue($p->getXuid(), 'wait_action');
        $p->setImmobile();
        $p->sendMessage('サーバー移動の準備中です...');

        $user = SQLManager::$manager->getUser($p->getXuid());
        $user->save(function () use ($server, $p) {
            $xuid = $p->getXuid();
            $name = $p->getName();
            if (AccountManager::hasValue($xuid, 'player_data_save')) {
                AccountManager::setValue($xuid, 'player_data_save', 0);
                AccountManager::setValue($xuid, 'transfer_server');
                $pk = new TransferPacket();
                $pk->address = $server;
                $p->sendMessage('サーバーを移動しています');
                $p->sendDataPacket($pk);
                CoralReefPlugin::$plugin->getScheduler()->scheduleDelayedTask(new ClosureTask(function (int $currentTick) use ($name): void {
                    $p = Server::getInstance()->getPlayer($name);
                    if (is_null($p)) return;
                    $xuid = $p->getXuid();
                    if (AccountManager::hasValue($xuid, 'transfer_server')) {
                        AccountManager::setValue($xuid, 'transfer_server', 0);
                        AccountManager::setValue($p->getXuid(), 'wait_action', 0);
                        $p->setImmobile(false);
                        $p->sendMessage('サーバーを移動出来ませんでした');
                    }
                }), 20 * 3);
            } else AccountManager::setValue($xuid, 'player_data_save');
        });
    }
}
