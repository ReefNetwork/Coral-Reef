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

namespace ree_jp\coral_reef\async\socket;

use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use RuntimeException;
use Socket;
use Thread;
use Threaded;

class ConnectionThread extends Thread
{
    public bool $isStop = false;
    private Threaded $sendQueue;
    private Threaded $receiveQueue;

    public function __construct(private string $address, private int $port, private int $mInterval)
    {
        $this->sendQueue = new Threaded();
        $this->receiveQueue = new Threaded();

        $this->start();
    }

    public function onRun(): void
    {
        $logger = new Logger("CoralReefSocket");
        $logger->pushHandler(new StreamHandler("php://stdout", Logger::INFO));
        $logger->notice("ソケットサーバーへ接続中です...");

        $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        if (($socket === false) || !socket_connect($socket, $this->address, $this->port)) {
            throw new RuntimeException(socket_strerror(socket_last_error()));
        }

        socket_set_nonblock($socket);
        $logger->notice("ソケットサーバーへの接続が確立されました");

        while (true) {
            usleep($this->mInterval);

            while ($this->sendQueue->count() > 0) {
                $data = unserialize($this->sendQueue->shift());
                if (!$this->send($socket, $data)) {
                    $logger->warning("ソケットサーバーへのデータ転送に失敗しました" . $data);
                }
            }

            while (($data = $this->receive($socket)) !== "") {
                if ($data === false) {
                    $logger->warning("ソケットサーバーへのデータ受け取りに失敗しました");
                    continue;
                }

                $this->receiveQueue[] = serialize($data);
            }

            if ($this->isStop) {
                break;
            }
        }
        $this->close($socket);
        $logger->notice("ソケットサーバーへの接続を閉鎖しました");
    }

    private function send(Socket $socket, string $data): bool
    {
        return socket_write($socket, $data, strlen($data)) !== false;
    }

    private function receive(Socket $socket): string|false
    {
        return socket_read($socket, 1024);
    }

    private function close(Socket $socket): void
    {
        socket_close($socket);
    }

    public function addSendQueue(string $data): void
    {
        $this->sendQueue[] = serialize($data);
    }

    public function takeReceiveQueue(): ?string
    {
        if ($this->receiveQueue->count() > 0) {
            return unserialize($this->receiveQueue->shift());
        }
        return null;
    }
}