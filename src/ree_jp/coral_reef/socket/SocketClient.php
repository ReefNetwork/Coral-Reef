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

use Logger;
use pocketmine\scheduler\TaskScheduler;

class SocketClient
{
    public SocketConnection $connection;

    public function __construct(private SocketHandler $handler, private Logger $logger, private TaskScheduler $scheduler, private string $address, private int $port, private int $tick)
    {
        $this->create();
    }

    private function create(): void
    {
        $this->connection = new SocketConnection($this->logger, $this->address, $this->port, $this->handler, $this->scheduler, $this->tick);
    }

    public function send(SocketData $data): bool
    {
        $json = json_encode($data);
        if ($json !== false) {
            return $this->connection->send($json);
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
        if (isset($this->connection)) {
            $this->connection->close();
            unset($this->connection);
        }
    }
}
