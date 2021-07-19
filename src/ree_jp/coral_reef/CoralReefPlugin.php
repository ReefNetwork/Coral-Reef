<?php

namespace ree_jp\coral_reef;

use PDOException;
use pocketmine\plugin\PluginBase;
use ree_jp\coral_reef\discord\DiscordBot;
use ree_jp\coral_reef\sql\SQLManager;

class CoralReefPlugin extends PluginBase
{
    const NOTICE = "§a>> ";
    static CoralReefPlugin $plugin;

    public DiscordBot $discordBot;

    public function onLoad()
    {
        self::$plugin = $this;

//        require_once "vendor/autoload.php";
//        $bot = new Discord(['token' => $this->getConfig()->get(ConfigConst::DISCORD_TOKEN)]);
//        $bot->run();

        try {
            SQLManager::$manager = new SQLManager("CoralReef",
                $this->getConfig()->get(ConfigConst::MYSQL_HOST), "pmmp",
                $this->getConfig()->get(ConfigConst::MYSQL_PASSWORD));
        } catch (PDOException $e) {
            $this->getLogger()->critical("[SQL]" . $e->getMessage());
        }

        $this->discordBot = new DiscordBot(
            $this->getFile(),
            $this->getConfig()->get(ConfigConst::DISCORD_TOKEN),
            $this->getConfig()->get(ConfigConst::DISCORD_CHAT_CHANNEL_ID),
            $this->getConfig()->get(ConfigConst::DISCORD_LOG_CHANNEL_ID));

        $this->getLogger()->info(self::NOTICE . "Load {$this->getName()}\nVer {$this->getDescription()->getVersion()}");
        parent::onLoad();
    }

    public function onEnable()
    {
        $this->discordBot->start();
        $this->getServer()->getPluginManager()->registerEvents(new EventListener(), $this);
        parent::onEnable();
    }

    public function onDisable()
    {
        $this->discordBot->close();
        parent::onDisable();
    }
}
