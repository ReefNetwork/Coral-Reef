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

namespace ree_jp\coral_reef;

use Frago9876543210\EasyForms\EasyForms;
use PDOException;
use pocketmine\plugin\PluginBase;
use ree_jp\coral_reef\discord\DiscordBot;
use ree_jp\coral_reef\sql\SQLManager;
use ree_jp\coral_reef\task\DataSaveTask;

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
        date_default_timezone_set('Asia/Tokyo');
        $this->discordBot->start();
        $this->getServer()->getPluginManager()->registerEvents(new EventListener(), $this);
        $this->getScheduler()->scheduleRepeatingTask(new DataSaveTask(), 20);

        $this->getServer()->getPluginManager()->registerEvents(new EasyForms(), $this);
        parent::onEnable();
    }

    public function onDisable()
    {
        $this->discordBot->close();
        parent::onDisable();
    }
}
