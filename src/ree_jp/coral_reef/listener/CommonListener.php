<?php /** @noinspection PhpUnused */

/*
 *  CCCCC                        lll RRRRRR                 fff
 * CC    C  oooo  rr rr    aa aa lll RR   RR   eee    eee  ff
 * CC      oo  oo rrr  r  aa aaa lll RRRRRR  ee   e ee   e ffff
 * CC    C oo  oo rr     aa  aaa lll RR  RR  eeeee  eeeee  ff
 *  CCCCC   oooo  rr      aaa aa lll RR   RR  eeeee  eeeee ff
 *
 * Copyright (c) 2022. Ree-jp(https://ree-jp.net)
 */

namespace ree_jp\coral_reef\listener;

use Generator;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerLoginEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\item\VanillaItems;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use ree_jp\coral_reef\account\AccountService;
use ree_jp\coral_reef\account\AccountStore;
use ree_jp\coral_reef\account\UserAccount;
use ree_jp\coral_reef\CoralReefPlugin;
use ree_jp\coral_reef\session\SessionStore;
use ree_jp\coral_reef\sql\model\PlayerData;
use ree_jp\coral_reef\sql\model\WarpPoint;
use ree_jp\coral_reef\sql\mysql\SQLRepository;
use ree_jp\coral_reef\sql\repo\PlayerRepository;
use ree_jp\coral_reef\sql\repo\UserRepository;
use ree_jp\coral_reef\sql\repo\WarpRepository;
use ree_jp\coral_reef\sql\RepositoryPool;
use ree_jp\coral_reef\StoreHouse;
use ree_jp\reef_edge\ReefEdgePlugin;
use ree_jp\reef_edge\socket\SocketService;
use SOFe\AwaitGenerator\Await;

class CommonListener implements Listener
{
    public function __construct(private RepositoryPool $pool, private StoreHouse $store)
    {
    }

    public function onPreLogin(PlayerLoginEvent $ev): void
    {
        $p = $ev->getPlayer();
        $await = [$this->loadUser($p), $this->loadPlayerData($this->pool, $p)];

        Await::f2c(function () use ($p, $await): Generator {
            /** @var AccountStore */
            $accountStore = $this->store->get(AccountStore::class);

            yield from Await::all($await);
            if (!$p->isConnected()) return;

            // データ読み込めたら動けるように
            $p->setImmobile(false);
            $accountStore->setValue($p->getXuid(), "wait_action", 0);

            if (!$p->isConnected()) return;

            $accountStore->getUser($p->getXuid())->loaded = true;
            $p->sendMessage("データを読み込みました");
        });
    }

    private function loadUser(Player $p): Generator
    {
        /** @var UserRepository */
        $repo = $this->pool->get(UserRepository::class);
        /** @var AccountStore */
        $accountStore = $this->store->get(AccountStore::class);

        $xuid = $p->getXuid();
        $user = yield from $repo->getUserData($p->getXuid());
        if ($user instanceof UserAccount) {
            $accountStore->users[$xuid] = $user;
        } else {
            $accountStore->users[$xuid] = new UserAccount($xuid, $p->getName(), 0, null);
            SocketService::sendBroadcastMessage(ReefEdgePlugin::$socketClient, TextFormat::AQUA . $p->getName() . "さんが初めてサーバーにログインしました");
        }
    }

    private function loadPlayerData(RepositoryPool $pool, Player $p): Generator
    {
        /** @var PlayerRepository */
        $repo = $pool->get(PlayerRepository::class);
        $data = yield from $repo->getPlayerData($p->getXuid());
        if (!$data instanceof PlayerData) return;
        $p->getInventory()->setContents($data->inv);
        $p->getArmorInventory()->setContents($data->armorInv);
        $p->getOffHandInventory()->setContents($data->offHandInv);
        $p->getEnderInventory()->setContents($data->enderInv);
        $p->getEffects()->clear();
        foreach ($data->effects as $effect) {
            $p->getEffects()->add($effect);
        }
        $p->setHealth($data->health);
        $p->getHungerManager()->setFood($data->hunger);
        $p->getXpManager()->addXp($data->xp);
    }

    private function warpAutoSavePoint(RepositoryPool $pool, Player $p): Generator
    {
        /** @var WarpRepository */
        $repo = $pool->get(WarpRepository::class);
        /** @var WarpPoint[] */
        $warps = yield from $repo->getWarps($p->getXuid(), CoralReefPlugin::$serverID);
        foreach ($warps as $warp) {
            if ($warp->warpName != AccountService::autoSaveWarpName()) continue;
            AccountService::teleport($p, $warp->pos->getWorld()->getFolderName(), $warp->pos);
        }
    }

    public function onJoin(PlayerJoinEvent $ev): void
    {
        /** @var SQLRepository */
        $repo = $this->pool->get(SQLRepository::class);
        /** @var AccountStore */
        $accountStore = $this->store->get(AccountStore::class);
        /** @var SessionStore */
        $sessionStore = $this->store->get(SessionStore::class);

        $p = $ev->getPlayer();
        $xuid = $p->getXuid();

        if ($accountStore->hasValue($xuid, "wait_action")) {
            $p->sendMessage("データを確認しています...");
        }
        Await::g2c($this->warpAutoSavePoint($this->pool, $p));
        AccountService::userJoin($repo, $accountStore, $p);
        $sessionStore->createSession($xuid, CoralReefPlugin::$serverID);

        $ev->setJoinMessage(""); // プロキシ側で参加メッセージを流す
        $p->sendTitle(TextFormat::GREEN . "Reef " . TextFormat::YELLOW . "Server");
        if (!$p->getInventory()->contains(VanillaItems::STICK())) {
            $p->getInventory()->addItem(VanillaItems::STICK()->setCustomName("メニューを開く"));
        }
    }

    public function onQuit(PlayerQuitEvent $ev): void
    {
        $p = $ev->getPlayer();
        /** @var AccountStore */
        $accountStore = $this->store->get(AccountStore::class);
        /** @var SessionStore */
        $sessionStore = $this->store->get(SessionStore::class);

        AccountService::userQuit($this->pool, $accountStore, $p);
        $sessionStore->destruction($this->pool, $p->getXuid());
        $ev->setQuitMessage(""); // プロキシ側で退出メッセージを流す
    }
}