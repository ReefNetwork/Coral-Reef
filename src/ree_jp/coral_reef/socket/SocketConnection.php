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
use pocketmine\scheduler\ClosureTask;
use pocketmine\scheduler\TaskHandler;
use pocketmine\scheduler\TaskScheduler;
use RuntimeException;
use Socket;

class SocketConnection
{
    private Socket|bool $socket;
    private TaskHandler $readTask;

    public function __construct(private Logger $logger, string $address, int $port, SocketHandler $handler, TaskScheduler $scheduler, int $interval)
    {
        $this->logger->notice("ソケットを準備中です...");
        $this->socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        if (($this->socket === false) || !socket_connect($this->socket, $address, $port)) {
            throw new RuntimeException(socket_strerror(socket_last_error()));
        }
        socket_set_nonblock($this->socket);

        $this->logger->notice("ソケットの準備が完了しました");
        $this->readTask = $scheduler->scheduleRepeatingTask(new ClosureTask(function () use ($handler): void {
            while (($data = socket_read($this->socket, 1024)) !== false && $data !== "") {
                $handler->handle($data);
            }
        }), $interval);
    }

    public function send(string $data): bool
    {
        return socket_write($this->socket, $data, strlen($data)) !== false;
    }

    public function close(): void
    {
        $this->readTask->run();
        $this->readTask->cancel();
        socket_set_block($this->socket);
        socket_close($this->socket);
        $this->logger->notice("ソケットサーバーへの接続を終了しました");
    }
}
