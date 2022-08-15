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
use pocketmine\crafting\ShapedRecipe;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\item\VanillaItems;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\ClosureTask;
use pocketmine\Server;
use pocketmine\world\generator\Flat;
use pocketmine\world\generator\normal\Normal;
use pocketmine\world\WorldCreationOptions;
use ree_jp\coral_reef\account\AccountStore;
use ree_jp\coral_reef\account\ScoreBoardService;
use ree_jp\coral_reef\command\BlockLogCommand;
use ree_jp\coral_reef\command\MenuCommand;
use ree_jp\coral_reef\command\ReefAdminCommand;
use ree_jp\coral_reef\command\ReefCommand;
use ree_jp\coral_reef\command\ReefConsoleCommand;
use ree_jp\coral_reef\command\ReefFormCommand;
use ree_jp\coral_reef\command\TrashCommand;
use ree_jp\coral_reef\gatya\items\ReefItems;
use ree_jp\coral_reef\item\CustomItemService;
use ree_jp\coral_reef\land\LandStore;
use ree_jp\coral_reef\listener\CommonListener;
use ree_jp\coral_reef\listener\RedstoneListener;
use ree_jp\coral_reef\money\MoneyCache;
use ree_jp\coral_reef\proxy\SocketHandler;
use ree_jp\coral_reef\quest\QuestListener;
use ree_jp\coral_reef\session\SessionStore;
use ree_jp\coral_reef\shop\ShopStore;
use ree_jp\coral_reef\sql\mysql\MysqlLandRepo;
use ree_jp\coral_reef\sql\mysql\MysqlPlayerDataRepo;
use ree_jp\coral_reef\sql\mysql\MysqlSessionRepo;
use ree_jp\coral_reef\sql\mysql\MysqlUserRepo;
use ree_jp\coral_reef\sql\mysql\MysqlWarpRepo;
use ree_jp\coral_reef\sql\mysql\SQLRepository;
use ree_jp\coral_reef\sql\repo\LandRepository;
use ree_jp\coral_reef\sql\repo\PlayerRepository;
use ree_jp\coral_reef\sql\repo\SessionRepository;
use ree_jp\coral_reef\sql\repo\UserRepository;
use ree_jp\coral_reef\sql\repo\WarpRepository;
use ree_jp\coral_reef\sql\RepositoryPool;
use ree_jp\coral_reef\task\DataSaveTask;
use ree_jp\coral_reef\task\EffectTask;
use ree_jp\coral_reef\task\SendServerTipTask;
use ree_jp\coral_reef\task\ServerUpdateTask;
use ree_jp\reef_edge\ReefEdgePlugin;
use SOFe\AwaitGenerator\Await;

class CoralReefPlugin extends PluginBase
{
    static CoralReefPlugin $plugin;
    static string $serverID;
    static string $serverDisplay;

    public bool $isDev = false;
    public bool $isMain = false;

    private SQLRepository $sqlRepo;
    public RepositoryPool $pool;
    public StoreHouse $store;

    public function onLoad(): void
    {
        self::$plugin = $this;
        ReefEdgePlugin::$isSocketStartUp = false;
    }

    public function onEnable(): void
    {
        self::$serverID = $this->getConfig()->get(ConfigConst::SERVER_NAME);
        self::$serverDisplay = $this->getConfig()->get(ConfigConst::SERVER);
        $this->isDev = !str_contains($this->getDescription()->getVersion(), 'stable');
        $this->isMain = self::$serverID === "seichi_1";

        if (!InvMenuHandler::isRegistered()) {
            InvMenuHandler::register($this);
        }
        date_default_timezone_set('Asia/Tokyo');
        $this->store = new StoreHouse();
        $this->store->register(new AccountStore(), AccountStore::class);
        $this->store->register(new ShopStore($this->getDataFolder()), ShopStore::class);
        $this->store->register(new SessionStore(), SessionStore::class);

        $this->initRepository();

        $this->store->register(new LandStore($this->pool), LandStore::class);

        $this->registerCommands();
        $this->registerListeners();
        $this->registerSchedules();
        $this->registerRecipe();
        $this->loadWorlds();

        /** @var AccountStore */
        $accountStore = $this->store->get(AccountStore::class);

        SocketHandler::register(ReefEdgePlugin::$socketHandler, $this->pool, $this->store);
        ReefEdgePlugin::$isSocketStartUp = true;
        if (isset(ReefEdgePlugin::$socketClient) && !ReefEdgePlugin::$socketClient->isConnected()) {
            ReefEdgePlugin::$socketClient->connect();
        }
        Await::g2c($accountStore->updateUserNameList($this->pool));
        ReefItems::registerAll();
        CustomItemService::registerAll();
        $this->pluginInformation();
    }

    public function onDisable(): void
    {
        foreach ($this->getServer()->getOnlinePlayers() as $p) {
            $p->kick("サーバーを停止します", false);
        }
        $this->pool->close();
    }

    public function criticalError(string $detail): void
    {
        $this->getLogger()->critical("致命的なエラーが発生しました: " . $detail);
        $this->getServer()->getPluginManager()->disablePlugin($this);
    }

    private function registerListeners(): void
    {
        /** @var AccountStore */
        $accountStore = $this->store->get(AccountStore::class);
        /** @var LandStore */
        $landStore = $this->store->get(LandStore::class);
        /** @var ShopStore */
        $shopStore = $this->store->get(ShopStore::class);
        /** @var SessionStore */
        $sessionStore = $this->store->get(SessionStore::class);

        $this->getServer()->getPluginManager()->registerEvents(new EventListener($this->pool, $this->store, $accountStore, $landStore,
            $shopStore, $sessionStore), $this);
        $this->getServer()->getPluginManager()->registerEvents(new QuestListener(), $this); // クエスト用
        $this->getServer()->getPluginManager()->registerEvents(new CommonListener($this->pool, $this->store), $this);
        $this->getServer()->getPluginManager()->registerEvents(new RedstoneListener($this->store), $this);
    }

    private function registerCommands(): void
    {
        /** @var AccountStore */
        $accountStore = $this->store->get(AccountStore::class);
        /** @var LandStore */
        $landStore = $this->store->get(LandStore::class);

        $this->getServer()->getCommandMap()->registerAll("reef", [
            new MenuCommand($this, $this->pool, $accountStore),
            new BlockLogCommand($this, $accountStore),
            new TrashCommand($this),
            new ReefCommand($this),
            new ReefAdminCommand($this, $this->pool, $this->store),
            new ReefConsoleCommand($this, $this->pool, $this->store),
            new ReefFormCommand($this, $this->pool, $this->store),
        ]);
    }

    private function registerSchedules(): void
    {
        $this->getScheduler()->scheduleRepeatingTask(new SendServerTipTask(), 15);
        $this->getScheduler()->scheduleRepeatingTask(new DataSaveTask($this->pool, $this->store), 20);
        $this->getScheduler()->scheduleRepeatingTask(new EffectTask(), 200);
        $this->getScheduler()->scheduleRepeatingTask(new ServerUpdateTask($this->sqlRepo), 200);
        $this->getScheduler()->scheduleRepeatingTask(new ClosureTask(function (): void {
            foreach (Server::getInstance()->getOnlinePlayers() as $p) ScoreBoardService::sendScoreBoard($this->store, $p);
        }), 15);
        $this->getScheduler()->scheduleRepeatingTask(new ClosureTask(function (): void {
            MoneyCache::purgeAll($this->sqlRepo);
        }), 20 * 10);
    }

    private function registerRecipe(): void
    {
        foreach ([ItemIds::COAL_ORE => VanillaItems::COAL(), ItemIds::IRON_ORE => VanillaItems::IRON_INGOT(), ItemIds::GOLD_ORE => VanillaItems::GOLD_INGOT(),
                     ItemIds::DIAMOND_ORE => VanillaItems::DIAMOND(), ItemIds::EMERALD_ORE => VanillaItems::EMERALD()] as $oreID => $result) {
            $ore = ItemFactory::getInstance()->get($oreID);
            $result->setCount(8);
            $this->getServer()->getCraftingManager()->registerShapedRecipe(new ShapedRecipe(["AAA", "ABA", "AAA"], ["A" => $ore, "B" => VanillaItems::COAL()], [$result]));
            $this->getServer()->getCraftingManager()->registerShapedRecipe(new ShapedRecipe(["AAA", "ABA", "AAA"], ["A" => $ore, "B" => VanillaItems::CHARCOAL()], [$result]));
        }
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

    private function initRepository(): void
    {
        $isInit = $this->getConfig()->get(ConfigConst::IS_SQL_INIT);
        $this->getLogger()->info("[SQL] サーバーに接続中...");
        $this->pool = new RepositoryPool($this, $this->getDataFolder());
        $this->getLogger()->info("[SQL] 準備しています");
        $this->sqlRepo = new SQLRepository($this->pool, $isInit);
        $this->pool->register($this->sqlRepo, SQLRepository::class);
        $this->pool->register(new MysqlPlayerDataRepo($this->pool, $isInit), PlayerRepository::class);
        $this->pool->register(new MysqlUserRepo($this->pool, $isInit), UserRepository::class);
        $this->pool->register(new MysqlWarpRepo($this->pool, $isInit), WarpRepository::class);
        $this->pool->register(new MysqlLandRepo($this->pool, $isInit), LandRepository::class);
        $this->pool->register(new MysqlSessionRepo($this->pool, $isInit), SessionRepository::class);
        $this->pool->getConnection()->waitAll();
        $this->getLogger()->info("[SQL] 完了しました");
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
