<?php


use Discord\Discord;
use Discord\Exceptions\IntentException;
use Discord\Parts\Channel\Channel;
use Discord\Parts\Channel\Message;
use Discord\Parts\Embed\Embed;
use Discord\WebSockets\Event;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use ree_jp\coral_reef\ConfigConst;
use ree_jp\coral_reef\CoralReefPlugin;

class DiscordBot
{
    private Discord $bot;
    private Channel $chat_channel;
    private Channel $log_channel;

    /**
     * @throws Exception
     */
    public function __construct(string $token, string $chat_id, string $log_id)
    {
        try {
            $this->bot = new Discord(['token' => $token]);
            $this->bot->on('ready', function (Discord $discord) {
                $discord->on(Event::MESSAGE_CREATE, function (Discord $discord, Message $message) {
                    if ($message->channel_id === CoralReefPlugin::$plugin->getConfig()->get(ConfigConst::DISCORD_CHAT_CHANNEL_ID)) {
                        $user = $message->user;
                        if (is_null($user) || $user->bot) return;

                        Server::getInstance()->broadcastMessage(
                            "<[" . TextFormat::DARK_PURPLE . "Discord" . TextFormat::WHITE . "]$user->username#$user->discriminator> $message->content");
                    }
                });
            });
        } catch (IntentException $e) {
            throw new Exception("discord botの初期化中にエラーが発生しました");
        }
        $chat = $this->bot->getChannel($chat_id);
        if (is_null($chat)) throw new Exception("チャットを送信するチャンネル($chat_id)が見つかりませんでした");
        $this->chat_channel = $chat;

        $log = $this->bot->getChannel($log_id);
        if (is_null($log)) throw new Exception("ログを送信するチャンネル($log_id)が見つかりませんでした");
        $this->log_channel = $log;

        $this->sendStartMessage();
    }

    public function sendChat(string $chat): void
    {
        try {
            $this->chat_channel->sendMessage("\"" . str_replace("\"", "", $chat) . "\"");
        } catch (Exception $e) {
            Server::getInstance()->getLogger()->error("チャットをdiscordに送信できませんでした" . $e->getMessage());
        }
    }

    public function sendLog(string $log): void
    {
        try {
            $this->log_channel->sendMessage($log);
        } catch (Exception $e) {
            Server::getInstance()->getLogger()->error("ログをdiscordに送信できませんでした" . $e->getMessage());
        }
    }

    public function close(): void
    {
        $embed = new Embed($this->bot);
        $embed->setTitle("サーバーが停止されます");
        $embed->setDescription("すぐに再起動されます");
        try {
            $this->chat_channel->sendEmbed($embed);
            $this->sendLog("サーバーが停止されます");
        } catch (Exception $e) {
            Server::getInstance()->getLogger()->error("discordに送信できませんでした" . $e->getMessage());
        }
        $this->bot->close();
    }

    private function sendStartMessage(): void
    {
        $embed = new Embed($this->bot);
        $embed->setTitle("サーバーが起動しました");
        $embed->setDescription(date("Y/m/d H:i:s") . " Version : " . Server::getInstance()->getVersion());
        try {
            $this->chat_channel->sendEmbed($embed);
            $this->sendLog("サーバーが起動しました");
        } catch (Exception $e) {
            Server::getInstance()->getLogger()->error("discordに送信できませんでした" . $e->getMessage());
        }
    }
}
