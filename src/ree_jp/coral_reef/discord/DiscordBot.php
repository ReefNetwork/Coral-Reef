<?php
/*
 *  CCCCC                        lll RRRRRR                 fff
 * CC    C  oooo  rr rr    aa aa lll RR   RR   eee    eee  ff
 * CC      oo  oo rrr  r  aa aaa lll RRRRRR  ee   e ee   e ffff
 * CC    C oo  oo rr     aa  aaa lll RR  RR  eeeee  eeeee  ff
 *  CCCCC   oooo  rr      aaa aa lll RR   RR  eeeee  eeeee ff
 *
 * Copyright (c) 2021-2021. Ree-jp(https://ree-jp.net)
 */

namespace ree_jp\coral_reef\discord;

use pocketmine\Thread;

class DiscordBot extends Thread
{
    private discordThread $client;
    private string $chat_id;
    private string $log_id;

    public function __construct(string $file, string $token, string $server_id, string $chat_id, string $log_id)
    {
        $this->chat_id = $chat_id;
        $this->log_id = $log_id;
        $this->client = new discordThread($file, $token, $server_id, $chat_id, 2);
    }

    public function sendChat(string $chat): void
    {
        $this->client->sendMessage(str_replace('@', '`art`', $chat), $this->chat_id);
    }

    public function sendLog(string $log): void
    {
        $this->client->sendMessage(str_replace('@', '`art`', $log), $this->log_id);
    }

    public function sendStartMessage(): void
    {
        $this->client->sendMessage('サーバーが起動しました', $this->chat_id, [date("Y/m/d H:i:s")]);
    }

    public function close(): void
    {
        $this->client->shutdown();
    }
}
