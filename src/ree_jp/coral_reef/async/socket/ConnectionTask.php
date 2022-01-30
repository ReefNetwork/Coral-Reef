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

use pocketmine\scheduler\AsyncTask;
use ree_jp\coral_reef\CoralReefPlugin;
use ree_jp\coral_reef\socket\SocketClient;
use ree_jp\coral_reef\socket\SocketConnection;

class ConnectionTask extends AsyncTask
{
    public function __construct(private string $address, private int $port, SocketClient $client)
    {
        $this->storeLocal("client", $client);
    }

    public function onRun(): void
    {
        $connection = new SocketConnection($this->address, $this->port);
        $this->setResult($connection);
    }

    public function onCompletion(): void
    {
        $client = $this->fetchLocal("client");
        if ($this->isCrashed() || (!$client instanceof SocketClient)) {
            CoralReefPlugin::$plugin->getLogger()->critical("プロキシサーバーへ接続できませんでした");
        } else {
            $client->connection = $this->getResult();
            CoralReefPlugin::$plugin->getLogger()->notice("プロキシサーバーへの接続を確立しました");
        }
    }
}