<?php

/**
 * https://github.com/DaisukeDaisuke/pmmpDiscordBot/blob/libAPI/src/pmmpDiscordBot/discordThread.php
 * (C) DaisukeDaisuke(https://github.com/DaisukeDaisuke)
 */

namespace ree_jp\coral_reef\discord;

use Discord\Discord;
use Discord\Exceptions\IntentException;
use Discord\Parts\Channel\Channel;
use Discord\Parts\Channel\Message;
use Discord\Parts\User\Member;
use Discord\Parts\WebSockets\MessageReaction;
use Monolog\Logger;
use pocketmine\utils\TextFormat;
use React\EventLoop\Loop;
use stdClass;
use Thread;
use Threaded;

class discordThread extends Thread
{
    const MESSAGE_TYPE = 0;//int
    const MESSAGE = 1;//string
    const MESSAGE_ID = 2;//string
    const MESSAGE_GUILD_ID = 3;//string
    const MESSAGE_CHANNEL_ID = 4;//string
    const MESSAGE_USERNAME = 5;//string
    const MESSAGE_USERID = 6;//string
    const MESSAGE_IS_BOT = 7;//bool
    const MESSAGE_IS_MYSELF = 8;//bool
    const MESSAGE_IS_DM = 9;//bool

    const MESSAGE_EMBEDS = 10;

    const MESSAGE_EMOJI = 11;//絵文字本体「🤔」等...
    const MESSAGE_EMOJI_ID = 12;//????? 絵文字Id「null」等...
    const MESSAGE_DISCRIMINATOR = 13;//string "8555" etc...
    const MESSAGE_CONTENTS_TYPE = 14;//int 0-15 @see https://github.com/teamreflex/DiscordPHP/blob/master/src/Discord/Parts/Channel/Message.php#L64

    const MESSAGE_CONTENTS_NORMAL = 0;
    //const MESSAGE = 1;
    //const MESSAGE_ID = 2;
    const MESSAGE_TYPE_REPLY = -1;
    const MESSAGE_TYPE_SEND = 0;
    const MESSAGE_TYPE_EDIT = 1;
    const MESSAGE_TYPE_EMOJI_ADD = 2;
    const MESSAGE_TYPE_EMOJI_REMOVE = 3;
    const MESSAGE_TYPE_MEMBER_ADD = 4;
    const MESSAGE_TYPE_MEMBER_REMOVE = 5;
    const MESSAGE_TYPE_DELETE = 6;
    public string $file;
    public bool $stopped = false;
    public bool $started = false;
    public string $content;
    public int $send_interval;
    public int $receive_check_interval;
    protected $D2P_Queue;
    protected $P2D_Queue;
    private string $token;

    public function __construct($file, string $token, int $send_interval = 1)
    {
        $this->file = $file;
        $this->token = $token;

        $this->send_interval = $send_interval;

        $this->D2P_Queue = new Threaded;
        $this->P2D_Queue = new Threaded;

        $this->start();
    }

    /**
     * @throws IntentException
     */
    public function run()
    {
        /** @noinspection PhpIncludeInspection */
        include_once $this->file . "vendor/autoload.php";

        $loop = Loop::get();
        $discord = new Discord([
            'token' => $this->token,
            "loop" => $loop,
            'loggerLevel' => Logger::WARNING,
        ]);
        unset($this->token);

        $discord->on('ready', function (Discord $discord) use ($loop): void {
            $this->started = true;
            echo "Bot is ready.", PHP_EOL;

            $timerForStop = $loop->addPeriodicTimer(1, function () use ($discord) {
                if ($this->stopped) {
                    $discord->close();
                    $discord->getLoop()->stop();
                    $this->started = false;
                }
            });

            $timerForSend = $loop->addPeriodicTimer(1, function () use ($discord) {
                $this->task($discord);
            });

            // Listen for events here
            $botUserId = $discord->user->id;
            // = $this->receive_channelId;

            $discord->on('message', function (Message $message) use ($botUserId): void {//, $receive_channelId
                //$message->react("🤔");
                if ($message->type !== Message::TYPE_NORMAL) {
                    return;//join message etc...
                }

                $this->D2P_Queue[] = serialize([
                    self::MESSAGE_TYPE => self::MESSAGE_TYPE_REPLY,//
                    self::MESSAGE => $message->content,
                    self::MESSAGE_ID => $message->id,
                    self::MESSAGE_GUILD_ID => $message->channel->guild_id ?? null,
                    self::MESSAGE_CHANNEL_ID => $message->channel_id,
                    self::MESSAGE_USERNAME => $message->author->username,
                    self::MESSAGE_USERID => $message->author->id,
                    self::MESSAGE_IS_BOT => $message->author->bot ?? false,
                    self::MESSAGE_IS_MYSELF => ($message->author->id === $botUserId),
                    self::MESSAGE_IS_DM => $message->channel->is_private,
                    //self::MESSAGE_CONTENTS_TYPE => $message->type,
                    //'md5' => md5($message["content"]),
                ]);
            });
            $discord->on("MESSAGE_REACTION_ADD", function (MessageReaction $reaction, Discord $discord) use ($botUserId) {
                $this->D2P_Queue[] = serialize([
                    self::MESSAGE_TYPE => self::MESSAGE_TYPE_EMOJI_ADD,//

                    self::MESSAGE_ID => $reaction->message_id,
                    self::MESSAGE_GUILD_ID => $reaction->guild_id,
                    self::MESSAGE_CHANNEL_ID => $reaction->channel_id,
                    //self::MESSAGE_USERNAME => $reaction->member->user->username,
                    self::MESSAGE_USERID => $reaction->user_id,

                    self::MESSAGE_IS_MYSELF => ($reaction->user_id === $botUserId),
                    //self::MESSAGE_IS_DM => $reaction->channel->is_private,

                    self::MESSAGE_EMOJI => $reaction->emoji->name,
                    self::MESSAGE_EMOJI_ID => $reaction->emoji->id,
                ]);
            });
            $discord->on("MESSAGE_REACTION_REMOVE", function (MessageReaction $reaction, Discord $discord) use ($botUserId) {
                $this->D2P_Queue[] = serialize([
                    self::MESSAGE_TYPE => self::MESSAGE_TYPE_EMOJI_REMOVE,//

                    self::MESSAGE_ID => $reaction->message_id,
                    self::MESSAGE_GUILD_ID => $reaction->guild_id,
                    self::MESSAGE_CHANNEL_ID => $reaction->channel_id,
                    //self::MESSAGE_USERNAME => $reaction->member->user->username,
                    self::MESSAGE_USERID => $reaction->user_id,

                    self::MESSAGE_IS_MYSELF => ($reaction->user_id === $botUserId),
                    //self::MESSAGE_IS_DM => $reaction->channel->is_private,

                    self::MESSAGE_EMOJI => $reaction->emoji->name,
                    self::MESSAGE_EMOJI_ID => $reaction->emoji->id,
                ]);
            });
            $discord->on("GUILD_MEMBER_ADD", function (Member $member, Discord $discord) use ($botUserId) {
                $this->D2P_Queue[] = serialize([
                    self::MESSAGE_TYPE => self::MESSAGE_TYPE_MEMBER_ADD,//

                    self::MESSAGE_GUILD_ID => $member->guild_id,
                    self::MESSAGE_USERNAME => $member->user->username,
                    self::MESSAGE_USERID => $member->user->id,

                    self::MESSAGE_IS_BOT => $member->user->bot ?? false,
                    self::MESSAGE_IS_MYSELF => ($member->user->id === $botUserId),

                    self::MESSAGE_DISCRIMINATOR => $member->user->discriminator,
                ]);
            });
            $discord->on("GUILD_MEMBER_REMOVE", function (Member $member, Discord $discord) use ($botUserId) {
                $this->D2P_Queue[] = serialize([
                    self::MESSAGE_TYPE => self::MESSAGE_TYPE_MEMBER_REMOVE,//

                    self::MESSAGE_GUILD_ID => $member->guild_id,
                    self::MESSAGE_USERNAME => $member->user->username,
                    self::MESSAGE_USERID => $member->user->id,

                    self::MESSAGE_IS_BOT => $member->user->bot ?? false,
                    self::MESSAGE_IS_MYSELF => ($member->user->id === $botUserId),

                    self::MESSAGE_DISCRIMINATOR => $member->user->discriminator,
                    //joined_at...?
                ]);
            });

            $discord->on("MESSAGE_DELETE", function ($obj, Discord $discord) use ($botUserId) {
                /** @var stdClass|Message $obj */
                if ($obj instanceof Message) {
                    //Message is present in cache
                    $this->D2P_Queue[] = serialize([
                        self::MESSAGE_TYPE => self::MESSAGE_TYPE_DELETE,//
                        self::MESSAGE_ID => $obj->id,
                        self::MESSAGE_CHANNEL_ID => $obj->channel_id,//...?
                    ]);
                    return;
                }
                $this->D2P_Queue[] = serialize([
                    self::MESSAGE_TYPE => self::MESSAGE_TYPE_DELETE,//

                    self::MESSAGE_ID => $obj->id,
                    self::MESSAGE_CHANNEL_ID => $obj->channel_id,
                ]);
            });
        });
        $discord->run();
    }

    public function task(Discord $discord)
    {
        if (!$this->started) return;
        $send = "";

        while (count($this->P2D_Queue) > 0) {
            $message = unserialize($this->P2D_Queue->shift());
            switch ($message[self::MESSAGE_TYPE]) {
                case self::MESSAGE_TYPE_SEND:
                    $channel = $discord->factory(Channel::class, ['id' => $message[self::MESSAGE_CHANNEL_ID], 'guild_id' => 638760361369010177]);
                    $send = preg_replace(['/\]0;.*\%/', '/[\x07]/', "/Server thread\//"], '', TextFormat::clean(substr($message[self::MESSAGE], 0, 1900)));//processtile,ANSIコードの削除を実施致します...
                    if ($send === "") continue 2;

                    $channel->sendMessage($send, false, $message[self::MESSAGE_EMBEDS] ?? null);//message, tts message, embeds message
                    break;
                case self::MESSAGE_TYPE_EDIT:
                    $this->messageUpdate($discord, $message[self::MESSAGE_ID], $message[self::MESSAGE_CHANNEL_ID], $message[self::MESSAGE]);
                    break;
            }
        }

    }

    public function messageUpdate($discord, string $messageId, $channel, string $contents)
    {
        $channel = $channel instanceof Channel ? $channel : $discord->factory(Channel::class, ['id' => $channel]);
        $message = $discord->factory(Message::class, ['id' => $messageId]);
        $channel->editMessage($message, $contents);
    }

    //===メインスレッド呼び出し専用関数にてございます...===

    public function shutdown()
    {
        $this->stopped = true;
    }

    public function replyMessage(string $userId, string $channelId, string $message, ?array $embeds = null)
    {
        $this->sendMessage("<@" . $userId . ">, " . $message, $channelId, $embeds);
    }

    public function sendMessage(string $message, string $channelId, ?array $embeds = null)
    {
        //var_dump("send".$message);
        $this->P2D_Queue[] = serialize([
            self::MESSAGE_TYPE => self::MESSAGE_TYPE_SEND,
            self::MESSAGE => $message,
            self::MESSAGE_CHANNEL_ID => $channelId,
            self::MESSAGE_EMBEDS => $embeds,
        ]);
    }

    public function editMessage(string $messageId, string $channelId, string $message)
    {
        $this->P2D_Queue[] = serialize([
            self::MESSAGE_TYPE => self::MESSAGE_TYPE_EDIT,
            self::MESSAGE => $message,
            self::MESSAGE_ID => $messageId,
            self::MESSAGE_CHANNEL_ID => $channelId,
            //self::MESSAGE_EMBEDS => $embeds,
        ]);
    }

    public function fetchMessages(): array
    {
        $messages = [];
        while (count($this->D2P_Queue) > 0) {
            $messages[] = unserialize($this->D2P_Queue->shift());
        }
        return $messages;
    }
}
