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

use alemiz\sga\StarGateAtlantis;
use pocketmine\Player;
use pocketmine\scheduler\ClosureTask;
use ree_jp\coral_reef\account\AccountManager;
use ree_jp\coral_reef\CoralReefPlugin;
use ree_jp\coral_reef\proxy\packet\ProxyCommandExecutePacket;
use ree_jp\coral_reef\sql\SQLManager;
use Throwable;

class ProxyManager
{
    static function registerPackets(): void
    {
        foreach (StarGateAtlantis::getInstance()->getClients() as $client) {
            $codec = $client->getProtocolCodec();
            $codec->registerPacket(ProxyPackets::COMMAND_EXECUTE, new ProxyCommandExecutePacket());
        }
    }

    static function transferServerWithSave(Player $p, string $server): void
    {
        AccountManager::setValue($p->getXuid(), 'wait_action');
        $p->setImmobile();
        $p->sendMessage('サーバー移動の準備中です...');

        $user = SQLManager::$manager->getUser($p->getXuid());
        $user->save(function () use ($server, $p) {
            AccountManager::setValue($p->getXuid(), 'save_xp');
            self::transferServer($p, $server, true);
        }, function () use ($server, $p) {
            AccountManager::setValue($p->getXuid(), 'save_skill');
            self::transferServer($p, $server, true);
        }, function () use ($server, $p) {
            AccountManager::setValue($p->getXuid(), 'save_quest');
            self::transferServer($p, $server, true);
        });
    }

    static private function transferServer(Player $p, string $address, bool $isCheckSafe): void
    {
        $xuid = $p->getXuid();
        // isCheckSafeの場合すべてセーブされたか確認する
        if (!$isCheckSafe || AccountManager::hasValue($xuid, 'save_xp') &&
            AccountManager::hasValue($xuid, 'save_skill') && AccountManager::hasValue($xuid, 'save_quest')) {
            if ($isCheckSafe) {
                AccountManager::setValue($p->getXuid(), 'save_xp', 0);
                AccountManager::setValue($p->getXuid(), 'save_skill', 0);
                AccountManager::setValue($p->getXuid(), 'save_quest', 0);
            }
            $p->sendMessage('サーバーを移動しています');
            try {
                StarGateAtlantis::getInstance()->transferPlayer($p, $address);
            } catch (Throwable $e) {
                $p->sendMessage("エラーが発生しました");
            }
            CoralReefPlugin::$plugin->getScheduler()->scheduleDelayedTask(new ClosureTask(function (int $currentTick) use ($p): void {
                if ($p->isClosed()) return;
                $xuid = $p->getXuid();
                AccountManager::setValue($xuid, 'transfer_server', 0);
                AccountManager::setValue($xuid, 'wait_action', 0);
                $p->setImmobile(false);
                $p->sendMessage('サーバーを移動出来ませんでした');
            }), 20 * 3);
        }
    }
}
