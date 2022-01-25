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
use pocketmine\scheduler\ClosureTask;
use pocketmine\scheduler\TaskHandler;
use raklib\generic\Socket;
use ree_jp\coral_reef\CoralReefPlugin;
use RuntimeException;

class SocketClient
{
    private Socket|false $socket;

    private TaskHandler $tickTask;

    public function __construct(string $address, int $port, int $tick)
    {
        $this->socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        try {
            if (($this->socket === false) || !socket_connect($this->socket, $address, $port)) {
                throw new RuntimeException(socket_strerror(socket_last_error()));
            }
            $this->send("yes");
            socket_set_nonblock($this->socket);
        } catch (Exception $e) {
            CoralReefPlugin::$plugin->getLogger()->critical("プロキシサーバーへ接続できませんでした");
            CoralReefPlugin::$plugin->getLogger()->logException($e);
        }
        $this->tickTask = CoralReefPlugin::$plugin->getScheduler()->scheduleRepeatingTask(new ClosureTask(
            function (): void {
                $this->receive();
            }
        ), $tick);
    }

    public function send(string $data): bool
    {
        return socket_write($this->socket, $data, strlen($data)) !== false;
    }

    public function receive(): string
    {
        return socket_read($this->socket, 128);
    }

    public function close(): void
    {
        $this->tickTask->cancel();
        socket_close($this->socket);
    }
}