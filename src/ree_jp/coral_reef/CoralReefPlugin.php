<?php

namespace ree_jp\coral_reef;

use DiscordBot;
use Exception;
use pocketmine\plugin\PluginBase;

class CoralReefPlugin extends PluginBase
{
    const NOTICE = "§a>> ";
    static CoralReefPlugin $plugin;

    public DiscordBot $discordBot;

    public function onLoad()
    {
        self::$plugin = $this;
        try {
            $this->discordBot = new DiscordBot(
                $this->getConfig()->get(ConfigConst::DISCORD_TOKEN),
                $this->getConfig()->get(ConfigConst::DISCORD_CHAT_CHANNEL_ID),
                $this->getConfig()->get(ConfigConst::DISCORD_LOG_CHANNEL_ID));
        } catch (Exception $e) {
            $this->getLogger()->critical("[DiscordBot]" . $e->getMessage());
        }
        $this->getLogger()->info(self::NOTICE . "Load {$this->getName()}\nVer {$this->getDescription()->getVersion()}");
        parent::onLoad();
    }

    public function onEnable()
    {
        parent::onEnable();
    }

    public function onDisable()
    {
        $bot = $this->discordBot;
        $bot->sendLog("");
        parent::onDisable();
    }
}
