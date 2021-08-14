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

use Exception;
use pocketmine\entity\object\ExperienceOrb;
use pocketmine\entity\object\ItemEntity;
use pocketmine\scheduler\Task;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use ree_jp\coral_reef\sql\SQLManager;

class DataSaveTask extends Task
{
    const SHUTDOWN = 10800;
    const SAVE_INTERVAL = 900;
    private int $timer = self::SHUTDOWN;

    public function onRun(int $currentTick)
    {
        --$this->timer;
        switch ($this->timer % self::SAVE_INTERVAL) {
            case 60:
                Server::getInstance()->broadcastMessage(TextFormat::GRAY . '1分後にデータのセーブと地面に落ちているアイテムなどの削除を行います');
                break;
            case 15:
                Server::getInstance()->broadcastMessage(TextFormat::GRAY . '15秒後にデータのセーブと地面に落ちているアイテムなどの削除を行います');
                break;
            case 5:
                Server::getInstance()->broadcastMessage(TextFormat::GRAY . '5秒後にデータのセーブと地面に落ちているアイテムなどの削除を行います');
                break;
            case 0:
                Server::getInstance()->broadcastMessage(TextFormat::GRAY . 'データのセーブと地面に落ちているアイテムなどの削除を行います\n数秒かかります...');
                $start = microtime(true);
                $this->save();
                foreach (Server::getInstance()->getLevels() as $level) {
                    foreach ($level->getEntities() as $entity) {
                        if ($entity instanceof ItemEntity or $entity instanceof ExperienceOrb) {
                            $entity->close();
                        }
                    }
                }
                Server::getInstance()->broadcastMessage(TextFormat::GRAY . '完了しました(' . round(microtime(true) - $start, 5) . '秒)');
        }
        switch ($this->timer) {
            case 3600:
                Server::getInstance()->broadcastMessage(TextFormat::GRAY . '1時間後に再起動を行います');
                break;
            case 1800:
                Server::getInstance()->broadcastMessage(TextFormat::GRAY . '30分後に再起動を行います');
                break;
            case 600:
                Server::getInstance()->broadcastMessage(TextFormat::GRAY . '10分後に再起動を行います');
                break;
            case 60:
                Server::getInstance()->broadcastMessage(TextFormat::GRAY . '60秒後に再起動を行います');
                break;
            case 30:
                Server::getInstance()->broadcastMessage(TextFormat::GRAY . '30秒後に再起動を行います');
                break;
            case 5:
                Server::getInstance()->broadcastMessage(TextFormat::GRAY . '5秒後に再起動を行います');
                break;
        }
        if ($this->timer < 0) {
            Server::getInstance()->broadcastMessage(TextFormat::DARK_RED . '再起動中...');
            foreach (Server::getInstance()->getOnlinePlayers() as $p) {
                $p->kick(TextFormat::GREEN . 'Reef ' . TextFormat::YELLOW . 'Server\n\n再起動しています', false, '再起動');
            }
            Server::getInstance()->shutdown();
        }
    }

    private function save(): void
    {
        foreach (Server::getInstance()->getLevels() as $level) {
            $level->save(true);
        }
        foreach (Server::getInstance()->getOnlinePlayers() as $p) {
            try {
                SQLManager::$manager->getUser($p->getXuid())->save();
            } catch (Exception $e) {
                Server::getInstance()->getLogger()->error("[autoSave] {$p->getName()} の処理中に" . $e->getMessage());
            }
        }
    }
}
