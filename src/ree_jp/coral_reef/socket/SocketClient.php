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

use pocketmine\scheduler\ClosureTask;
use pocketmine\scheduler\TaskHandler;
use pocketmine\scheduler\TaskScheduler;
use ree_jp\coral_reef\async\socket\ConnectionThread;

class SocketClient
{
    public ConnectionThread $connection;

    private TaskHandler $tickTask;

    public function __construct(SocketHandler $handler, private TaskScheduler $scheduler, private string $address, private int $port, private int $tick)
    {
        $this->create();
        $this->tickTask = $this->scheduler->scheduleRepeatingTask(new ClosureTask(function () use ($handler): void {
            while (!is_null($data = $this->connection->takeReceiveQueue())) {
                $handler->handle($data);
            }
        }), $tick);
    }

    private function create(): void
    {
        $this->connection = new ConnectionThread($this->address, $this->port, $this->tick * 50000);
    }

    public function send(SocketData $data): bool
    {
        $json = json_encode($data);
        if ($json !== false) {
            $this->connection->addSendQueue($json);
            return true;
        }
        return false;
    }

    public function reconnect(): void
    {
        $this->close();
        $this->create();
    }

    public function close(): void
    {
        $this->tickTask->cancel();
        if (isset($this->connection)) {
            // ソケットを閉じる処理は動機的に
            $this->connection->isStop = true;
            $this->connection->join();
        }
    }
}
