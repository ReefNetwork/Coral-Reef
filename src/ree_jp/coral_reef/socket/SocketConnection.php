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
use ree_jp\coral_reef\CoralReefPlugin;
use RuntimeException;
use Socket;

class SocketConnection
{
    private Socket|false $socket;

    public function __construct(string $address, int $port)
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
    }

    public function send(string $data): bool
    {
        return socket_write($this->socket, $data, strlen($data)) !== false;
    }

    public function receive(): string|false
    {
        return socket_read($this->socket, 1024);
    }

    public function close(): void
    {
        socket_close($this->socket);
    }
}