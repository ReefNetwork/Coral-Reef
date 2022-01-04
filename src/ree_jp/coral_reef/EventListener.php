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
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\block\BlockUpdateEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityTeleportEvent;
use pocketmine\event\inventory\InventoryCloseEvent;
use pocketmine\event\inventory\InventoryTransactionEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerGameModeChangeEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerItemConsumeEvent;
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
use ree_jp\coral_reef\form\command\land\LandForm;
use ree_jp\coral_reef\form\item\HerbicideForm;
use ree_jp\coral_reef\gatya\items\ReefItems;
use ree_jp\coral_reef\gatya\items\SpecialItemService;
use ree_jp\coral_reef\land\LandService;
use ree_jp\coral_reef\land\LandStore;
use ree_jp\coral_reef\session\SessionStore;
use ree_jp\coral_reef\shop\ShopService;
use ree_jp\coral_reef\shop\ShopStore;
use ree_jp\coral_reef\sql\SettingConst;
use ree_jp\coral_reef\sql\SQLRepository;
use ree_jp\coral_reef\task\EffectTask;
use Throwable;

class EventListener implements Listener
{
    public function __construct(private ?SQLRepository $sqlRepo, private AccountStore $accountStore, private LandStore $landStore,
                                private ShopStore      $shopStore, private SessionStore $sessionStore)
    {
    }

    public function onPreLogin(PlayerLoginEvent $ev): void
    {
        if (is_null($this->sqlRepo)) {
            $ev->getPlayer()->kick(TextFormat::GREEN . "Reef" . TextFormat::YELLOW . "Server" .
                TextFormat::DARK_RED . "データの読み込み中に予期せぬエラーが発生しました");
        }
        $this->sqlRepo->loadUser($ev->getPlayer());
    }

    public function onJoin(PlayerJoinEvent $ev): void
    {
        $p = $ev->getPlayer();
        $xuid = $p->getXuid();

        if (is_null($this->accountStore->getUser($xuid))) { // データをまだ読み込めてなかったら動きを止める
            $this->accountStore->setValue($xuid, 'wait_action');
            $p->setImmobile();
            $p->sendMessage('データを確認しています...');
        }
        AccountService::userJoin($this->sqlRepo, $this->accountStore, $p);
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

        AccountService::userQuit($this->sqlRepo, $this->accountStore, $p);
        $this->sessionStore->destruction($this->sqlRepo, $p->getXuid());
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
            Server::getInstance()->broadcastMessage(TextFormat::DARK_GRAY . '[死亡] ' . $p->getDisplayName());
            $p->sendTitle("死亡しました");
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
        if (LandService::protect($this->landStore, $this->accountStore, $p, $ev->getBlock()->getPosition(), 'このワールドでブロックを掘ることはできません')) {
            $ev->cancel();
            return;
        }
        if ($this->accountStore->hasValue($p->getXuid(), 'skill_cool_time') &&
            !SettingManager::isEnableOption($p->getXuid(), SettingConst::ALLOW_COOL_TIME_DIG)) {
            $p->sendPopup("クールタイム中はブロックを掘ることはできません");
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

        try {
            AccountService::blockBroken($this->sqlRepo, $this->accountStore, $p, $ev->getBlock(), $this->sessionStore->getSessionData($p->getXuid()));
        } catch (Exception $e) {
            $p->sendMessage('エラーが発生しました');
            Server::getInstance()->getLogger()->error('[blockBroke]' . $p->getName() . 'の処理中に' . $e->getMessage());
        }
        try {
            foreach ($ev->getDrops() as $dropItem) {
                /**
                 * @noinspection PhpUndefinedNamespaceInspection
                 * @noinspection PhpUndefinedClassInspection
                 * @noinspection PhpFullyQualifiedNameUsageInspection
                 */
                \ree_jp\stackStorage\api\StackStorageAPI::$instance->add($p->getXuid(), $dropItem);
            }
            $ev->setDrops([]);
        } catch (Throwable) { // StackStorageAPIが見つからなかった場合
            $p->sendMessage('ストレージにアクセスできませんでした');
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
            "このワールドでブロックを設置することはできません")) {
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

    public function onUpdate(BlockUpdateEvent $ev)
    {
        $blId = $ev->getBlock()->getId();
        if (($blId === BlockLegacyIds::FLOWING_WATER) || ($blId === BlockLegacyIds::WATER)) {
            $ev->cancel();
        }
    }

    public function onTouch(PlayerInteractEvent $ev): void
    {
        $p = $ev->getPlayer();
        $xuid = $p->getXuid();
        if ($this->accountStore->hasValue($xuid, 'wait_action')) {
            $ev->cancel();
            return;
        }

        switch ($ev->getItem()->getId()) {
            case ItemIds::FLINT_STEEL:
                $ev->cancel();
                $p->kick(TextFormat::DARK_RED . "このアイテムは使用出来ません");
                break;

            case ItemIds::STICK:
                Server::getInstance()->dispatchCommand($p, "menu");
                break;

            case ItemIds::CLOCK:
                if ($p->isSneaking()) {
                    if ($this->accountStore->hasValue($xuid, 'particle_cool_time')) return;
                    $this->accountStore->setValue($xuid, 'particle_cool_time', 20);
                    LandService::checkSpace($this->landStore, $p);
                } else {
                    if ($this->accountStore->hasValue($xuid, 'form_cool_time')) return;
                    $this->accountStore->setValue($xuid, 'form_cool_time', 10);
                    LandForm::sendLandCreateAssistForm($this->sqlRepo, $this->accountStore, $this->landStore, $p, $ev->getBlock()->getPosition());
                }
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
        switch ($ev->getBlock()->getId()) {
            case BlockLegacyIds::SIGN_POST:
            case BlockLegacyIds::WALL_SIGN:
                if ($this->accountStore->hasValue($xuid, 'form_cool_time')) return;
                $this->accountStore->setValue($xuid, 'form_cool_time', 10);
                ShopService::showShop($this->sqlRepo, $p, $this->shopStore, $ev->getBlock()->getPosition());
                break;
        }
        if (($ev->getBlock()->getId() === BlockLegacyIds::FRAME_BLOCK) &&
            LandService::protect($this->landStore, $this->accountStore, $p, $ev->getBlock()->getPosition(), "このワールドで額縁を変更することはできません")) {
            $ev->cancel();
            return;
        }
        if (LandService::protect($this->landStore, $this->accountStore, $p, $ev->getBlock()->getPosition(), null, true)) {
            $ev->cancel();
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

        if (in_array($toLevelName, AccountService::STOP_FLY_WORLD) && $this->accountStore->hasValue($p->getXuid(), 'fly')) {
            $this->accountStore->setValue($p->getXuid(), 'fly', 0);
            $p->setFlying(false);
            $p->setAllowFlight(false);
            $p->sendMessage('このワールドで飛行することはできません');
        }
        if ($isWorldChange) {
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
        $tag = $nbt->getByte(ReefItems::REEF_SP_ITEM, 99999);
        if (($tag !== 99999) && $p->getHungerManager()->isHungry()) {
            CoralReefPlugin::$plugin->getScheduler()->scheduleDelayedTask(new ClosureTask(function () use ($food, $p): void {
                $p->getInventory()->addItem($food->setCount(1));
            }), 3);
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
        $p = $ev->getPlayer();
        if ($this->accountStore->hasValue($p->getXuid(), 'fly')) {
            $this->accountStore->setValue($p->getXuid(), 'fly', 0);
            $p->setFlying(false);
            $p->setAllowFlight(false);
            $p->sendMessage('モードが変わったため飛行を無効化しました');
        }
    }
}
