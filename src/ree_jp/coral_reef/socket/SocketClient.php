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

class SocketClient
{
    private SocketConnection $client;
    private TaskHandler $tickTask;

    public function __construct(private SocketHandler $handler, private TaskScheduler $scheduler, private string $address, private int $port, private int $tick)
    {
        $this->create();
    }

    private function create(): void
    {
        $this->client = new SocketConnection($this->address, $this->port);
        $this->tickTask = $this->scheduler->scheduleRepeatingTask(new ClosureTask(
            function (): void {
                $content = $this->client->receive();
                if ($content === false) {
                    $this->reconnect();
                    return;
                }

                if ($content !== "") {
                    $this->handler->handle($content);
                }
            }
        ), $this->tick);
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
        $this->tickTask->cancel();
        $this->client->close();
    }
}