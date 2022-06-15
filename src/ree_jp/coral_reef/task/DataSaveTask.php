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

namespace ree_jp\coral_reef\task;

use pocketmine\entity\Entity;
use pocketmine\entity\object\ExperienceOrb;
use pocketmine\entity\object\ItemEntity;
use pocketmine\scheduler\ClosureTask;
use pocketmine\scheduler\Task;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use ree_jp\coral_reef\account\AccountStore;
use ree_jp\coral_reef\account\UserAccount;
use ree_jp\coral_reef\CoralReefPlugin;
use ree_jp\coral_reef\sql\RepositoryPool;
use ree_jp\reef_edge\ReefEdgePlugin;
use ree_jp\reef_edge\socket\SocketService;
use SOFe\AwaitGenerator\Await;

class DataSaveTask extends Task
{
    const SHUTDOWN = 10800;
    const SAVE_INTERVAL = 300;
    const WORLD_SAVE_INTERNAL = 1800;
    private int $timer = self::SHUTDOWN;

    /**
     * @var Entity[]
     */
    private array $entities = [];

    public function __construct(private RepositoryPool $pool, private AccountStore $store)
    {
    }

    public function onRun(): void
    {
        --$this->timer;
        if (($this->timer % self::SAVE_INTERVAL) === 0) {
            $this->dataSave();
        }
        switch ($this->timer % self::WORLD_SAVE_INTERNAL) {
            case 60:
                Server::getInstance()->broadcastMessage(TextFormat::GRAY . "1分後にデータのセーブと地面に落ちているアイテムなどの削除を行います");
                break;
            case 15:
                Server::getInstance()->broadcastMessage(TextFormat::GRAY . "15秒後にデータのセーブと地面に落ちているアイテムなどの削除を行います");
                break;
            case 5:
                Server::getInstance()->broadcastMessage(TextFormat::GRAY . "5秒後にデータのセーブと地面に落ちているアイテムなどの削除を行います");
                break;
            case 0:
                Server::getInstance()->broadcastMessage(TextFormat::GRAY . "データのセーブを行います\n数秒かかります...");
                $start = microtime(true);
                $this->worldSave();
                $message = "データをセーブしました(" . round(microtime(true) - $start, 2) . "秒)";
                Server::getInstance()->broadcastMessage(TextFormat::GRAY . $message);
                self::startClearItem();
        }
        switch ($this->timer) {
            case 3600:
                Server::getInstance()->broadcastMessage(TextFormat::GRAY . "1時間後に再起動を行います");
                break;
            case 1800:
                Server::getInstance()->broadcastMessage(TextFormat::GRAY . "30分後に再起動を行います");
                break;
            case 600:
                Server::getInstance()->broadcastMessage(TextFormat::GRAY . "10分後に再起動を行います");
                break;
            case 60:
                Server::getInstance()->broadcastMessage(TextFormat::GRAY . "60秒後に再起動を行います");
                break;
            case 30:
                Server::getInstance()->broadcastMessage(TextFormat::GRAY . "30秒後に再起動を行います");
                break;
            case 5:
                Server::getInstance()->broadcastMessage(TextFormat::GRAY . "5秒後に再起動を行います");
                SocketService::sendBroadcastMessage(ReefEdgePlugin::$socketClient, CoralReefPlugin::$server . "サーバーを§c定期再起動§rします...");
                break;
        }
        if ($this->timer < 0) {
            Server::getInstance()->broadcastMessage(TextFormat::DARK_RED . "再起動中...");
            foreach (Server::getInstance()->getOnlinePlayers() as $p) {
                $p->kick("再起動しています", false);
            }
            Server::getInstance()->shutdown();
        }
    }

    private function dataSave(): void
    {
        foreach (Server::getInstance()->getOnlinePlayers() as $p) {
            $user = $this->store->getUser($p->getXuid());
            if ($user instanceof UserAccount) Await::g2c($user->save($this->pool, $p));
        }
    }

    private function worldSave(): void
    {
        foreach (Server::getInstance()->getWorldManager()->getWorlds() as $level) {
            $level->save(true);
        }
    }

    private function startClearItem(): void
    {
        Server::getInstance()->broadcastMessage(TextFormat::GRAY . "10秒後に地面に落ちているアイテムなどの削除を行います");
        foreach (Server::getInstance()->getWorldManager()->getWorlds() as $world) {
            foreach ($world->getEntities() as $entity) {
                if ($entity instanceof ItemEntity or $entity instanceof ExperienceOrb) {
                    $this->entities[] = $entity;
                }
            }
        }
        CoralReefPlugin::$plugin->getScheduler()->scheduleDelayedTask(new ClosureTask(function (): void {
            $this->clearWarnItem();
        }), 20 * 10);
    }

    private function clearWarnItem(): void
    {
        foreach ($this->entities as $entity) {
            if (!$entity->isClosed()) {
                $entity->close();
            }
        }
        $this->entities = [];
        Server::getInstance()->broadcastMessage(TextFormat::GRAY . "地面に落ちているアイテムなどを削除しました");
    }
}
