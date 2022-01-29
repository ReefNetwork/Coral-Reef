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

use pocketmine\scheduler\TaskScheduler;
use pocketmine\Server;
use ree_jp\coral_reef\async\socket\ConnectionTask;

class SocketClient
{
    public SocketConnection $client;

//    private TaskHandler $tickTask;

    public function __construct(private SocketHandler $handler, private TaskScheduler $scheduler, private string $address, private int $port, private int $tick)
    {
        $this->create();
    }

    private function create(): void
    {
        Server::getInstance()->getAsyncPool()->submitTask(new ConnectionTask($this->address, $this->port, $this));
    }

    public function send(SocketData $data): bool
    {
        $json = json_encode($data);
        return ($json !== false) && $this->client->send($json);
    }

    public function reconnect(): void
    {
        $this->close();
        $this->create();
    }

    public function close(): void
    {
//        $this->tickTask->cancel();
        $this->client->close();
    }
}
