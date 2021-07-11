<?php


use Discord\Discord;
use Discord\Exceptions\IntentException;
use Discord\Parts\Channel\Channel;
use Discord\Parts\Channel\Message;
use Discord\WebSockets\Event;

class DiscordBot
{
    private Discord $bot;
    private Channel $log_channel;

    /**
     * @throws Exception
     */
    public function __construct(string $token, string $log_id)
    {
        try {
            $this->bot = new Discord(['token' => $token]);
            $this->bot->on('ready', function (Discord $discord) {
                $discord->on(Event::MESSAGE_CREATE, function (Discord $discord, Message $message) {

                });
            });
        } catch (IntentException $e) {
            throw new Exception("discord botの初期化中にエラーが発生しました");
        }
        $channel = $this->bot->getChannel($log_id);
        if (is_null($channel)) throw new Exception("ログを送信するチャンネル($log_id)が見つかりませんでした");
        $this->log_channel = $channel;
    }

    /**
     * @throws Exception
     */
    public function sendLog(string $log): void
    {
    }

    public function close(): void
    {
        $this->bot->close();
    }

}
