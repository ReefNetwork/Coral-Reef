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

use Exception;
use PDOException;
use pocketmine\level\generator\GeneratorManager;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\ClosureTask;
use pocketmine\Server;
use ree_jp\coral_reef\account\AccountManager;
use ree_jp\coral_reef\account\ScoreBoardManager;
use ree_jp\coral_reef\command\MenuCommand;
use ree_jp\coral_reef\command\ReefAdminCommand;
use ree_jp\coral_reef\command\ReefCommand;
use ree_jp\coral_reef\command\ReefConsoleCommand;
use ree_jp\coral_reef\gatya\items\ReefItems;
use ree_jp\coral_reef\land\LandManager;
use ree_jp\coral_reef\proxy\ProxyManager;
use ree_jp\coral_reef\quest\QuestListener;
use ree_jp\coral_reef\sql\SQLManager;
use ree_jp\coral_reef\task\DataSaveTask;
use ree_jp\coral_reef\task\EffectTask;
use ree_jp\coral_reef\task\SendServerTipTask;
use ree_jp\coral_reef\task\ServerUpdateTask;

class CoralReefPlugin extends PluginBase
{
    static CoralReefPlugin $plugin;

    public bool $isDev = false;

    private array $errors = [];

    public function onLoad()
    {
        self::$plugin = $this;
        $this->isDev = !strstr($this->getDescription()->getVersion(), 'stable');
    }

    public function onEnable()
    {
        date_default_timezone_set('Asia/Tokyo');
        try {
            SQLManager::$manager = new SQLManager($this->getDataFolder(), $this->getConfig()->get(ConfigConst::SERVER_NAME));
        } catch (PDOException $e) {
            $this->getLogger()->critical("[SQL]" . $e->getMessage());
        }
        try {
            LandManager::$instance = new LandManager();
        } catch (Exception $e) {
            $this->getLogger()->critical("[LandManager]" . $e->getMessage());
        }

        $this->getServer()->generateLevel("lobby", time(), GeneratorManager::getGenerator("flat"));
        $this->getServer()->generateLevel("main_1", time(), generatorManager::getGenerator("default"));
        $this->getServer()->generateLevel("main_2", time(), generatorManager::getGenerator("default"));
        $this->getServer()->loadLevel("lobby");
        $this->getServer()->loadLevel("main_1");
        $this->getServer()->loadLevel("main_2");

        $this->getServer()->getPluginManager()->registerEvents(new EventListener(), $this);
        $this->getServer()->getPluginManager()->registerEvents(new QuestListener(), $this); // クエスト用
        $this->getServer()->getCommandMap()->register('menu', new MenuCommand($this));
        $this->getServer()->getCommandMap()->register('reef', new ReefCommand($this));
        $this->getServer()->getCommandMap()->register('reef-admin', new ReefAdminCommand($this));
        $this->getServer()->getCommandMap()->register('reef-console', new ReefConsoleCommand($this));
        $this->getScheduler()->scheduleRepeatingTask(new SendServerTipTask(), 15);
        $this->getScheduler()->scheduleRepeatingTask(new DataSaveTask(), 20);
        $this->getScheduler()->scheduleRepeatingTask(new EffectTask(), 200);
        $this->getScheduler()->scheduleRepeatingTask(new ServerUpdateTask(), 20);
        $this->getScheduler()->scheduleRepeatingTask(new ClosureTask(function (int $currentTick): void {
            foreach (Server::getInstance()->getOnlinePlayers() as $p) ScoreBoardManager::sendScoreBoard($p);
        }), 15);

        ProxyManager::registerPackets();
        AccountManager::setUp();
        ReefItems::registerAll();
        $this->pluginInformation();
    }

    public function onDisable()
    {
        foreach ($this->getServer()->getOnlinePlayers() as $p) {
            $p->kick("サーバーを停止します", false);
        }
        if (!empty($this->getServer()->getOnlinePlayers())) sleep(1);
        if (!is_null(SQLManager::$manager)) SQLManager::$manager->close();
    }

    public function setError(string $error): void
    {
        $this->getLogger()->emergency($error);
        array_push($this->errors, $error);
    }

    public function isError(): ?array
    {
        if (empty($this->errors)) return null;
        return $this->errors;
    }

    private function pluginInformation(): void
    {
        $this->getLogger()->info('------------------------------------------------------------------------------------');
        $this->getLogger()->info(' CCCCC                        lll   RRRRRR                 fff');
        $this->getLogger()->info('CC    C  oooo  rr rr    aa aa lll   RR   RR   eee    eee  ff');
        $this->getLogger()->info('CC      oo  oo rrr  r  aa aaa lll   RRRRRR  ee   e ee   e ffff');
        $this->getLogger()->info('CC    C oo  oo rr     aa  aaa lll   RR  RR  eeeee  eeeee  ff');
        $this->getLogger()->info(' CCCCC   oooo  rr      aaa aa lll   RR   RR  eeeee  eeeee ff    ver ' . $this->getDescription()->getVersion());
        $this->getLogger()->info('by ree-jp(https://ree-jp.net) & ReefNetwork(https://reef.ree-jp.net) & oss');
        $this->getLogger()->info('------------------------------------------------------------------------------------');
    }
}
