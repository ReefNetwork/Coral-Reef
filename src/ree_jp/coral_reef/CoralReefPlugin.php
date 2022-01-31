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

use muqsit\invmenu\InvMenuHandler;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\ClosureTask;
use pocketmine\Server;
use pocketmine\world\generator\Flat;
use pocketmine\world\generator\normal\Normal;
use pocketmine\world\WorldCreationOptions;
use ree_jp\coral_reef\account\AccountStore;
use ree_jp\coral_reef\account\ScoreBoardService;
use ree_jp\coral_reef\command\MenuCommand;
use ree_jp\coral_reef\command\ReefAdminCommand;
use ree_jp\coral_reef\command\ReefCommand;
use ree_jp\coral_reef\command\ReefConsoleCommand;
use ree_jp\coral_reef\command\ReefFormCommand;
use ree_jp\coral_reef\command\TrashCommand;
use ree_jp\coral_reef\gatya\items\ReefItems;
use ree_jp\coral_reef\land\LandStore;
use ree_jp\coral_reef\money\MoneyCache;
use ree_jp\coral_reef\quest\QuestListener;
use ree_jp\coral_reef\session\SessionStore;
use ree_jp\coral_reef\shop\ShopStore;
use ree_jp\coral_reef\socket\SocketClient;
use ree_jp\coral_reef\socket\SocketHandler;
use ree_jp\coral_reef\sql\SQLRepository;
use ree_jp\coral_reef\task\DataSaveTask;
use ree_jp\coral_reef\task\EffectTask;
use ree_jp\coral_reef\task\SendServerTipTask;
use ree_jp\coral_reef\task\ServerUpdateTask;

class CoralReefPlugin extends PluginBase
{
    static CoralReefPlugin $plugin;

    public bool $isDev = false;
    public bool $isMain = false;

    private SQLRepository $sqlRepo;
    private AccountStore $accountStore;
    private LandStore $landStore;
    private ShopStore $shopStore;
    private SessionStore $sessionStore;

    private SocketClient $socketClient;

    public function onLoad(): void
    {
        self::$plugin = $this;
        $this->isDev = !str_contains($this->getDescription()->getVersion(), 'stable');
        /** @noinspection SpellCheckingInspection */
        $this->isMain = $this->getConfig()->get(ConfigConst::SERVER_NAME) === "seichi_1";
    }

    public function onEnable(): void
    {
        if (!InvMenuHandler::isRegistered()) {
            InvMenuHandler::register($this);
        }
        date_default_timezone_set('Asia/Tokyo');
        $this->accountStore = new AccountStore();
        $this->sqlRepo = new SQLRepository($this->accountStore, $this, $this->getDataFolder(), $this->getConfig()->get(ConfigConst::SERVER_NAME));
        $this->landStore = new LandStore($this->sqlRepo);
        $this->shopStore = new ShopStore($this->getDataFolder());
        $this->sessionStore = new SessionStore();

        $this->registerCommands();
        $this->registerListeners();
        $this->registerSchedules();
        $this->loadWorlds();

        $handler = new SocketHandler();
        $this->socketClient = new SocketClient($handler, $this->getScheduler(), $this->getConfig()->get(ConfigConst::SOCKET_SERVER_ADDRESS),
            $this->getConfig()->get(ConfigConst::SOCKET_SERVER_PORT), $this->getConfig()->get(ConfigConst::SOCKET_RECEIVE_INTERVAL));

        $this->accountStore->updateUserNameList($this->sqlRepo);
        ReefItems::registerAll();
        $this->pluginInformation();
    }

    public function onDisable(): void
    {
        foreach ($this->getServer()->getOnlinePlayers() as $p) {
            $p->kick("サーバーを停止します", false);
        }
        if (isset($this->sqlRepo)) {
            $this->sqlRepo->close();
        }
        if (isset($this->socketClient)) {
            $this->socketClient?->close();
        }
    }

    public function criticalError(string $detail): void
    {
        $this->getLogger()->critical("致命的なエラーが発生しました: " . $detail);
        $this->getServer()->getPluginManager()->disablePlugin($this);
    }

    private function registerListeners(): void
    {
        $this->getServer()->getPluginManager()->registerEvents(new EventListener($this->sqlRepo, $this->accountStore, $this->landStore,
            $this->shopStore, $this->sessionStore), $this);
        $this->getServer()->getPluginManager()->registerEvents(new QuestListener(), $this); // クエスト用
    }

    private function registerCommands(): void
    {
        $this->getServer()->getCommandMap()->registerAll("reef", [
            new MenuCommand($this, $this->sqlRepo, $this->accountStore),
            new TrashCommand($this),
            new ReefCommand($this),
            new ReefAdminCommand($this, $this->sqlRepo, $this->accountStore, $this->landStore),
            new ReefConsoleCommand($this, $this->accountStore),
            new ReefFormCommand($this, $this->sqlRepo, $this->accountStore, $this->landStore),
            new ReefConsoleCommand($this, $this->accountStore),
        ]);
    }

    private function registerSchedules(): void
    {
        $this->getScheduler()->scheduleRepeatingTask(new SendServerTipTask(), 15);
        $this->getScheduler()->scheduleRepeatingTask(new DataSaveTask($this->sqlRepo, $this->accountStore), 20);
        $this->getScheduler()->scheduleRepeatingTask(new EffectTask(), 200);
        $this->getScheduler()->scheduleRepeatingTask(new ServerUpdateTask($this->sqlRepo), 200);
        $this->getScheduler()->scheduleRepeatingTask(new ClosureTask(function (): void {
            foreach (Server::getInstance()->getOnlinePlayers() as $p) ScoreBoardService::sendScoreBoard($this->accountStore, $p);
        }), 15);
        $this->getScheduler()->scheduleRepeatingTask(new ClosureTask(function (): void {
            MoneyCache::purgeAll($this->sqlRepo);
        }), 20);
    }

    private function loadWorlds(): void
    {
        $wm = $this->getServer()->getWorldManager();
        if (!$wm->isWorldGenerated("lobby")) {
            $wm->generateWorld("lobby", WorldCreationOptions::create()->setGeneratorClass(Flat::class));
        }
        if (!$wm->isWorldGenerated("main_1")) {
            $wm->generateWorld("main_1", WorldCreationOptions::create()->setGeneratorClass(Normal::class));
        }
        if (!$wm->isWorldGenerated("main_2")) {
            $wm->generateWorld("main_2", WorldCreationOptions::create()->setGeneratorClass(Normal::class));
        }
        $wm->loadWorld("lobby");
        $wm->loadWorld("main_1");
        $wm->loadWorld("main_2");
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
