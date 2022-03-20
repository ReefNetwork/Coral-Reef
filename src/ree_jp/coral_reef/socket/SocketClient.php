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

namespace ree_jp\coral_reef\socket;

use Exception;
use Logger;
use pocketmine\scheduler\ClosureTask;
use pocketmine\scheduler\TaskScheduler;

class SocketClient
{
    public SocketConnection $connection;

    public function __construct(private SocketHandler $handler, private Logger $logger, private TaskScheduler $scheduler, private string $address, private int $port, private int $tick)
    {
        $this->create();
    }

    private function create(int $nextReconnectInterval = 5): void
    {
        try {
            $this->connection = new SocketConnection($this->logger, $this->address, $this->port, $this->handler, $this->scheduler, $this->tick);
        } catch (Exception $ex) {
            $this->logger->error("ソケットサーバーに接続中にエラーが発生しました");
            $this->logger->logException($ex);
            if ($nextReconnectInterval > 0) {
                $this->reConnect($nextReconnectInterval);
            }
            return;
        }
    }

    public function send(SocketData $data): bool
    {
        $json = json_encode($data);
        if ($json !== false) {
            return $this->connection->send($json);
        }
        return false;
    }

    public function reConnect(int $interval = 5): void
    {
        $this->logger->notice("$interval 秒後に再接続します...");
        $this->scheduler->scheduleDelayedTask(new ClosureTask(function () use ($interval): void {
            $this->create($interval * 2);
        }), $interval * 20);
    }

    public function close(): void
    {
        if (isset($this->connection)) {
            $this->connection->close();
            unset($this->connection);
        }
    }
}
