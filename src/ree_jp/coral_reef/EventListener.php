<?php /** @noinspection PhpUnused */

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
use pocketmine\block\BlockLegacyIds;
use pocketmine\block\Flowable;
use pocketmine\block\Liquid;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockBurnEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\block\BlockSpreadEvent;
use pocketmine\event\block\BlockUpdateEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityTeleportEvent;
use pocketmine\event\inventory\InventoryCloseEvent;
use pocketmine\event\inventory\InventoryTransactionEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerBucketEmptyEvent;
use pocketmine\event\player\PlayerBucketEvent;
use pocketmine\event\player\PlayerBucketFillEvent;
use pocketmine\event\player\PlayerDropItemEvent;
use pocketmine\event\player\PlayerGameModeChangeEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerItemConsumeEvent;
use pocketmine\event\player\PlayerItemUseEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerLoginEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\inventory\ArmorInventory;
use pocketmine\item\ItemIds;
use pocketmine\item\VanillaItems;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\player\Player;
use pocketmine\scheduler\ClosureTask;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use ree_jp\coral_reef\account\AccountService;
use ree_jp\coral_reef\account\AccountStore;
use ree_jp\coral_reef\account\SettingManager;
use ree_jp\coral_reef\form\item\HerbicideForm;
use ree_jp\coral_reef\form\land\LandForm;
use ree_jp\coral_reef\gatya\items\ReefItems;
use ree_jp\coral_reef\gatya\items\SpecialItemService;
use ree_jp\coral_reef\item\ClickItem;
use ree_jp\coral_reef\land\LandService;
use ree_jp\coral_reef\land\LandStore;
use ree_jp\coral_reef\session\SessionStore;
use ree_jp\coral_reef\shop\ShopService;
use ree_jp\coral_reef\shop\ShopStore;
use ree_jp\coral_reef\sql\mysql\SQLRepository;
use ree_jp\coral_reef\sql\RepositoryPool;
use ree_jp\coral_reef\sql\SettingConst;
use ree_jp\coral_reef\task\EffectTask;
use ree_jp\stackStorage\api\StackStorageAPI;
use Throwable;

class EventListener implements Listener
{
    public function __construct(private RepositoryPool $pool, private AccountStore $accountStore, private LandStore $landStore,
                                private ShopStore      $shopStore, private SessionStore $sessionStore)
    {
    }

    public function onPreLogin(PlayerLoginEvent $ev): void
    {
        /** @var SQLRepository */
        $sqlRepo = $this->pool->get(SQLRepository::class);
        $sqlRepo->loadUser($ev->getPlayer());
    }

    public function onJoin(PlayerJoinEvent $ev): void
    {
        $p = $ev->getPlayer();
        $xuid = $p->getXuid();
        /** @var SQLRepository */
        $sqlRepo = $this->pool->get(SQLRepository::class);

        if ($this->accountStore->hasValue($xuid, "wait_action")) { // データをまだ読み込めてなかったら動きを止める
            $this->accountStore->setValue($xuid, "wait_action");
            $p->setImmobile();
            $p->sendMessage("データを確認しています...");
        }
        AccountService::userJoin($sqlRepo, $this->accountStore, $p);
        $this->sessionStore->createSession($xuid);

        $ev->setJoinMessage(""); // プロキシ側で参加メッセージを流す
        $p->sendTitle(TextFormat::GREEN . 'Reef ' . TextFormat::YELLOW . 'Server');
        if (!$p->getInventory()->contains(VanillaItems::STICK())) {
            $p->getInventory()->addItem(VanillaItems::STICK()->setCustomName('メニューを開く'));
        }
    }

    public function onQuit(PlayerQuitEvent $ev): void
    {
        $p = $ev->getPlayer();
        /** @var SQLRepository */
        $sqlRepo = $this->pool->get(SQLRepository::class);

        AccountService::userQuit($this->pool, $this->accountStore, $p);
        $this->sessionStore->destruction($sqlRepo, $p->getXuid());
        $ev->setQuitMessage(""); // プロキシ側で退出メッセージを流す
    }

    public function onDamage(EntityDamageEvent $ev)
    {
        $p = $ev->getEntity();
        if (!$p instanceof Player) return;
        if ($this->accountStore->hasValue($p->getXuid(), 'wait_action')) {
            $ev->cancel();
            return;
        }

        $health = $p->getHealth();
        if ($ev instanceof EntityDamageByEntityEvent && $ev->getDamager() instanceof Player) {
            $ev->cancel();
            return;
        }
        switch ($ev->getCause()) {
            case EntityDamageEvent::CAUSE_FALL:
                $ev->setBaseDamage(0);
                break;

            case EntityDamageEvent::CAUSE_VOID:
                $health = -1;
                break;

            default:
                $ev->cancel();
        }
        if ($health <= $ev->getFinalDamage()) {
            $ev->cancel();
            $p->setHealth($p->getMaxHealth());
            $p->getHungerManager()->setFood($p->getHungerManager()->getMaxFood());
            $p->teleport(Server::getInstance()->getWorldManager()->getDefaultWorld()->getSpawnLocation());
            Server::getInstance()->broadcastMessage(TextFormat::DARK_GRAY . '[Death] ' . $p->getDisplayName());
            $p->sendActionBarMessage(TextFormat::GRAY . '死亡したため、スポーン地点に転送されました');
        }
    }

    /**
     * @priority LOW
     */
    public function onBreakLow(BlockBreakEvent $ev): void
    {
        $p = $ev->getPlayer();
        if ($this->accountStore->hasValue($p->getXuid(), 'wait_action')) {
            $ev->cancel();
            return;
        }
        if (LandService::protect($this->landStore, $this->accountStore, $p, $ev->getBlock()->getPosition(),
            "このワールドでブロックを掘ることはできません", false, !$ev->getBlock() instanceof Flowable)) {
            $ev->cancel();
            return;
        }
        if ($this->accountStore->hasValue($p->getXuid(), 'skill_cool_time') &&
            !SettingManager::isEnableOption($p->getXuid(), SettingConst::ALLOW_COOL_TIME_DIG)) {
            $p->sendPopup(TextFormat::GRAY . "クールタイム中にブロックを掘ることはできません!!");
            $ev->cancel();
        }
    }

    /**
     * @priority MONITOR
     */
    public function onBreakMonitor(BlockBreakEvent $ev): void
    {
        $p = $ev->getPlayer();
        if ($ev->isCancelled()) return;
        if ($this->accountStore->hasValue($p->getXuid(), "herbicide_break")) return;

        if ($p->isCreative() && !is_null($this->shopStore->findShop($ev->getBlock()->getPosition()))) {
            $this->shopStore->removeShop($ev->getBlock()->getPosition());
            $p->sendMessage(TextFormat::GREEN . "ショップを破壊しました");
        }

        /** @var SQLRepository */
        $sqlRepo = $this->pool->get(SQLRepository::class);

        try {
            AccountService::blockBroken($sqlRepo, $this->accountStore, $p, $ev->getBlock(), $this->sessionStore->getSessionData($p->getXuid()));
        } catch (Exception $e) {
            $p->sendMessage(TextFormat::RED . 'エラーが発生しました');
            Server::getInstance()->getLogger()->error('[blockBroke]' . $p->getName() . 'の処理中に' . $e->getMessage());
        }
        try {
            foreach ($ev->getDrops() as $dropItem) {
                StackStorageAPI::$instance->add($p->getXuid(), $dropItem);
            }
            $ev->setDrops([]);
        } catch (Throwable) { // StackStorageAPIが見つからなかった場合
            $p->sendMessage(TextFormat::RED . 'ストレージにアクセスできませんでした');
        }
    }

    /**
     * @priority LOW
     */
    public function onPlace(BlockPlaceEvent $ev): void
    {
        $p = $ev->getPlayer();
        if ($this->accountStore->hasValue($p, 'wait_action')) {
            $ev->cancel();
            return;
        }

        if (LandService::protect($this->landStore, $this->accountStore, $p, $ev->getBlock()->getPosition(),
            "このワールドでブロックを設置することはできません", false, true)) {
            $ev->cancel();
        }
    }

    /**
     * @priority MONITOR
     */
    public function onPlaceMonitor(BlockPlaceEvent $ev): void
    {
        $this->sessionStore->getSessionData($ev->getPlayer()->getXuid())->placeBlock();
    }

    public function onSpread(BlockSpreadEvent $ev)
    {
        if ($ev->getSource() instanceof Liquid || in_array($ev->getSource()->getPosition()->getWorld()->getFolderName(), LandService::LOBBY_WORLD)) {
            $ev->cancel();
        }
    }

    public function onBucketFill(PlayerBucketFillEvent $ev)
    {
        $this->onBucket($ev);
    }

    public function onBucketEmpty(PlayerBucketEmptyEvent $ev)
    {
        $this->onBucket($ev);
    }

    private function onBucket(PlayerBucketEvent $ev)
    {
        $p = $ev->getPlayer();
        if ($this->accountStore->hasValue($p, "wait_action")) {
            $ev->cancel();
            return;
        }

        if (LandService::protect($this->landStore, $this->accountStore, $p, $ev->getBlockClicked()->getPosition(),
            "このワールドでバケツを使用することはできません", false, true)) {
            $ev->cancel();
        }
    }

    public function onUpdate(BlockUpdateEvent $ev)
    {
        $blId = $ev->getBlock()->getId();
        if (($blId === BlockLegacyIds::FLOWING_WATER) || ($blId === BlockLegacyIds::WATER)) {
            $ev->cancel();
        }
    }

    public function onUse(PlayerItemUseEvent $ev): void
    {
        $p = $ev->getPlayer();
        $xuid = $p->getXuid();
        if ($this->accountStore->hasValue($xuid, 'wait_action')) {
            $ev->cancel();
            return;
        }

        switch ($ev->getItem()->getId()) {
            /** @noinspection PhpMissingBreakStatementInspection */
            case ItemIds::BUCKET:
                if ($ev->getItem()->getMeta() !== 10) {
                    break;
                }
            case ItemIds::FLINT_STEEL:
                $ev->cancel();
                $p->kick(TextFormat::DARK_RED . "このアイテムは使用出来ません");
                break;

            case ItemIds::STICK:
                Server::getInstance()->dispatchCommand($p, "menu");
                break;

            case ItemIds::DYE:
                $nbt = $ev->getItem()->getNamedTag();
                if ($nbt->getCompoundTag("herbicide_scale") instanceof CompoundTag) {
                    if ($this->accountStore->hasValue($xuid, 'form_cool_time')) break;
                    $this->accountStore->setValue($xuid, 'form_cool_time', 10);
                    HerbicideForm::sendForm($this->accountStore, $p);
                }
                break;
        }
    }

    public function onTouch(PlayerInteractEvent $ev): void
    {
        $p = $ev->getPlayer();
        $item = $ev->getItem();
        $xuid = $p->getXuid();
        /** @var SQLRepository */
        $sqlRepo = $this->pool->get(SQLRepository::class);
        if ($this->accountStore->hasValue($xuid, 'wait_action')) {
            $ev->cancel();
            return;
        }

        if ($item instanceof ClickItem) {
            $item->active($p);
        }

        switch ($item->getId()) {
            /** @noinspection PhpMissingBreakStatementInspection */
            case ItemIds::BUCKET:
                if ($item->getMeta() !== 10) {
                    break;
                }
            case ItemIds::FLINT_STEEL:
                $ev->cancel();
                $p->kick(TextFormat::DARK_RED . "このアイテムは使用出来ません");
                break;

            case ItemIds::COMPASS:
                if ($this->accountStore->hasValue($xuid, "form_cool_time")) return;
                $this->accountStore->setValue($xuid, "form_cool_time", 10);
                $pos = $ev->getBlock()->getPosition();
                Server::getInstance()->dispatchCommand($p, "block-log {$pos->getFloorX()} {$pos->getFloorY()} {$pos->getFloorZ()}");
                break;

            case ItemIds::CLOCK:
                if ($p->isSneaking()) {
                    if ($this->accountStore->hasValue($xuid, 'particle_cool_time')) return;
                    $this->accountStore->setValue($xuid, 'particle_cool_time', 20);
                    LandService::checkSpace($this->landStore, $p);
                } else {
                    if ($this->accountStore->hasValue($xuid, 'form_cool_time')) return;
                    $this->accountStore->setValue($xuid, 'form_cool_time', 10);
                    LandForm::sendLandCreateAssistForm($sqlRepo, $this->accountStore, $this->landStore, $p, $ev->getBlock()->getPosition());
                }
                break;

            case ItemIds::STICK:
                Server::getInstance()->dispatchCommand($p, "menu");
                break;

            case ItemIds::DYE:
                $nbt = $item->getNamedTag();
                if ($nbt->getCompoundTag("herbicide_scale") instanceof CompoundTag) {
                    if ($this->accountStore->hasValue($xuid, 'form_cool_time')) break;
                    $this->accountStore->setValue($xuid, 'form_cool_time', 10);
                    HerbicideForm::sendForm($this->accountStore, $p);
                }
                break;
        }

        switch ($ev->getBlock()->getId()) {
            case BlockLegacyIds::SIGN_POST:
            case BlockLegacyIds::WALL_SIGN:
                if ($this->accountStore->hasValue($xuid, 'form_cool_time')) return;
            $this->accountStore->setValue($xuid, 'form_cool_time', 10);
            ShopService::showShop($sqlRepo, $p, $this->shopStore, $ev->getBlock()->getPosition());
                break;
        }
        if (in_array($ev->getBlock()->getId(),
            [BlockLegacyIds::GRASS, BlockLegacyIds::DIRT, BlockLegacyIds::FRAME_BLOCK, BlockLegacyIds::CHEST, BlockLegacyIds::LECTERN])) {
            if (LandService::protect($this->landStore, $this->accountStore, $p, $ev->getBlock()->getPosition(),
                "このワールドでこのブロックに変更を加えることはできません")) {
                $ev->cancel();
                return;
            }
        }

        if (LandService::protect($this->landStore, $this->accountStore, $p, $ev->getBlock()->getPosition(), null, true)) {
            $ev->cancel();
        }
    }


    public function onDrop(PlayerDropItemEvent $ev): void
    {
        $p = $ev->getPlayer();
        if ($p->getInventory()->getItemInHand()->equals($ev->getItem())) {
            if (!$this->accountStore->hasValue($p->getXuid(), "allow_drop")) {
                $this->accountStore->setValue($p->getXuid(), "allow_drop", 20 * 10);
                $p->sendPopup("10秒以内にもう一度アイテムを落とそうとすると落とすことができます");
                $ev->cancel();
            }
        }
    }

    public function onTeleport(EntityTeleportEvent $ev): void
    {
        $p = $ev->getEntity();
        if (!$p instanceof Player) return;
        if ($this->accountStore->hasValue($p->getXuid(), 'wait_action')) {
            $ev->cancel();
            return;
        }
        $fromLevelName = $ev->getFrom()->getWorld()->getFolderName();
        $toLevelName = $ev->getTo()->getWorld()->getFolderName();
        $isWorldChange = $fromLevelName !== $toLevelName;

        if ($isWorldChange) {
            AccountService::updateFly($p, $ev->getTo()->world->getFolderName());
            unset($this->landStore->pos[$p->getXuid()]);
        }
    }

    public function onClose(InventoryCloseEvent $ev): void
    {
        $p = $ev->getPlayer();
        if ($this->accountStore->hasValue($p->getXuid(), "item_renew_cool_time")) return;
        $this->accountStore->setValue($p->getXuid(), "item_renew_cool_time", 20 * 60);

        foreach ($p->getInventory()->getContents() as $slot => $item) {
            $nbt = $item->getNamedTag();
            $xuid = $nbt->getString("owner", $p->getXuid());

            $renewItem = SpecialItemService::getRenewItem($xuid, $nbt->getString(ReefItems::REEF_SP_ITEM, "unknown"), $item->getMeta(),
                $this->accountStore);
            if (!is_null($renewItem) && !$item->equals($renewItem)) {
                $p->getInventory()->setItem($slot, $renewItem->setCount($item->getCount()));
            }
        }
    }

    /**
     * @priority MONITOR
     */
    public function onEat(PlayerItemConsumeEvent $ev): void
    {
        $p = $ev->getPlayer();
        $food = $ev->getItem();
        $nbt = $food->getNamedTag();
        $tag = $nbt->getByte("reef_infinite_food", 99999);
        if (($tag !== 99999) && $p->getHungerManager()->isHungry()) {
            CoralReefPlugin::$plugin->getScheduler()->scheduleDelayedTask(new ClosureTask(function () use ($food, $p): void {
                $p->getInventory()->addItem($food->setCount(1));
            }), 3);
        }
    }

    /**
     * @priority LOWEST
     */
    public function onBurn(BlockBurnEvent $ev): void
    {
        $ev->cancel();
    }

    /**
     * @priority LOWEST
     */
    public function onTransactionLowest(InventoryTransactionEvent $ev): void
    {
        if ($this->accountStore->hasValue($ev->getTransaction()->getSource()->getXuid(), "wait_action")) {
            $ev->cancel();
        }
    }

    /**
     * @priority MONITOR
     */
    public function onTransactionMonitor(InventoryTransactionEvent $ev): void
    {
        $transaction = $ev->getTransaction();
        foreach ($transaction->getInventories() as $inv) {
            if ($inv instanceof ArmorInventory) { // 防具を動かしたとき、エフェクトの更新をする
                CoralReefPlugin::$plugin->getScheduler()->scheduleDelayedTask(new ClosureTask(function () use ($transaction): void {
                    EffectTask::updateEffect($transaction->getSource());
                }), 3);
                return;
            }
        }
    }

    /**
     * @priority MONITOR
     */
    public function onModeChangeMonitor(PlayerGameModeChangeEvent $ev): void
    {
        CoralReefPlugin::$plugin->getScheduler()->scheduleDelayedTask(new ClosureTask(function () use ($ev): void {
            if ($ev->getPlayer()->isOnline()) {
                AccountService::updateFly($ev->getPlayer(), $ev->getPlayer()->getWorld()->getFolderName());
            }
        }), 3);
    }
}
