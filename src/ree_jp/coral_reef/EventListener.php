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
use pocketmine\block\BlockIds;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\block\BlockUpdateEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityTeleportEvent;
use pocketmine\event\inventory\InventoryTransactionEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerGameModeChangeEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerItemConsumeEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerLoginEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\inventory\ArmorInventory;
use pocketmine\item\Item;
use pocketmine\item\ItemIds;
use pocketmine\level\Position;
use pocketmine\network\mcpe\protocol\ItemFrameDropItemPacket;
use pocketmine\Player;
use pocketmine\scheduler\ClosureTask;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use ree_jp\coral_reef\account\AccountManager;
use ree_jp\coral_reef\account\SettingManager;
use ree_jp\coral_reef\form\FormManager;
use ree_jp\coral_reef\form\LandForm;
use ree_jp\coral_reef\land\LandManager;
use ree_jp\coral_reef\sql\SettingConst;
use ree_jp\coral_reef\sql\SQLManager;
use ree_jp\coral_reef\task\EffectTask;
use Throwable;

class EventListener implements Listener
{
    function onPreLogin(PlayerLoginEvent $ev): void
    {
        $p = $ev->getPlayer();
        $xuid = $p->getXuid();
        if (is_null(SQLManager::$manager)) {
            $p->kick(TextFormat::GREEN . 'Reef ' . TextFormat::YELLOW . 'Server' . TextFormat::DARK_RED . 'データベースサーバーが見つかりませんでした');
            return;
        }
        $error = CoralReefPlugin::$plugin->isError();
        if (!is_null($error)) {
            $p->kick(TextFormat::GREEN . 'Reef ' . TextFormat::YELLOW . 'Server' . TextFormat::DARK_RED . 'データの読み込み中に予期せぬエラーが発生しました');
            return;
        }
        SQLManager::$manager->loadUser($xuid, $p->getName());
        $reason = SQLManager::$manager->getBanReason($xuid, $p->getAddress());
        if (is_string($reason)) {
            $isNotShow = strstr($reason, '[BAN_NOT_SHOW]', true);
            if ($isNotShow === false) {
                $p->kick("Banされています" . TextFormat::EOL . $reason);
            } else {
                $p->kick(TextFormat::GREEN . 'Reef ' . TextFormat::YELLOW . 'Server' . TextFormat::DARK_RED . "\n\n$isNotShow");
            }
        }
    }

    function onJoin(PlayerJoinEvent $ev): void
    {
        $p = $ev->getPlayer();
        $xuid = $p->getXuid();

//        if (is_null(SQLManager::$manager->getUser($xuid))) { // データをまだ読み込めてなかったら動きを止める
//            AccountManager::setValue($xuid, 'wait_action');
//            $p->setImmobile();
//            $p->sendMessage('データを確認しています...');
//        }
        AccountManager::userJoin($p);

        $ev->setJoinMessage(""); // プロキシ側で参加メッセージを流す
        $p->sendTitle(TextFormat::GREEN . 'Reef ' . TextFormat::YELLOW . 'Server');
        if (!$p->getInventory()->contains(Item::get(ItemIds::STICK)))
            $p->getInventory()->addItem(Item::get(ItemIds::STICK)->setCustomName('メニューを開く'));
    }

    function onQuit(PlayerQuitEvent $ev): void
    {
        $p = $ev->getPlayer();

        AccountManager::userQuit($p);
        $ev->setQuitMessage(""); // プロキシ側で退出メッセージを流す
    }

    function onDamage(EntityDamageEvent $ev)
    {
        $p = $ev->getEntity();
        if (!$p instanceof Player) return;
        if (AccountManager::hasValue($p->getXuid(), 'wait_action')) {
            $ev->setCancelled();
            return;
        }

        $health = $p->getHealth();
        if ($ev instanceof EntityDamageByEntityEvent && $ev->getDamager() instanceof Player) {
            $ev->setCancelled();
            return;
        }
        switch ($ev->getCause()) {
            case EntityDamageEvent::CAUSE_FALL:
                $ev->setBaseDamage(0);
                break;

            case EntityDamageEvent::CAUSE_VOID:
                $health = -1;
                break;
        }
        if ($health <= $ev->getFinalDamage()) {
            $ev->setCancelled();
            $p->setHealth($p->getMaxHealth());
            $p->setFood($p->getMaxFood());
            $p->teleport(Server::getInstance()->getDefaultLevel()->getSpawnLocation());
            Server::getInstance()->broadcastMessage(TextFormat::DARK_GRAY . '[死亡] ' . $p->getDisplayName());
            $p->sendTitle("死亡しました");
        }
    }


    /**
     * @priority LOW
     */
    function onBreakLow(BlockBreakEvent $ev): void
    {
        $p = $ev->getPlayer();
        if (AccountManager::hasValue($p->getXuid(), 'wait_action')) {
            $ev->setCancelled();
            return;
        }

        $ev->setCancelled(LandManager::$instance->protect($p, $ev->getBlock(), 'このワールドでブロックを掘ることはできません'));
        if (AccountManager::hasValue($p->getXuid(), 'skill_cool_time') &&
            !SettingManager::isEnableOption($p->getXuid(), SettingConst::ALLOW_COOL_TIME_DIG)) {
            $p->sendPopup("クールタイム中はブロックを掘ることはできません");
            $ev->setCancelled();
        }
    }

    /**
     * @priority MONITOR
     */
    function onBreakMonitor(BlockBreakEvent $ev): void
    {
        $p = $ev->getPlayer();
        if ($ev->isCancelled()) return;
        try {
            AccountManager::brockBroken($p, $ev->getBlock(), $ev->getItem());
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
        } catch (Throwable $e) { // StackStorageAPIが見つからなかった場合
            $p->sendMessage('ストレージにアクセスできませんでした');
        }
    }

    /**
     * @priority LOW
     */
    function onPlace(BlockPlaceEvent $ev): void
    {
        $p = $ev->getPlayer();
        if (AccountManager::hasValue($p, 'wait_action')) {
            $ev->setCancelled();
            return;
        }

        $ev->setCancelled(LandManager::$instance->protect($p, $ev->getBlock(), 'このワールドでブロックを設置することはできません'));
    }

    function onUpdate(BlockUpdateEvent $ev)
    {
        $blId = $ev->getBlock()->getId();
        if (($blId === BlockIds::FLOWING_WATER) || ($blId === BlockIds::WATER)) {
            $ev->setCancelled();
        }
    }

    function onTouch(PlayerInteractEvent $ev): void
    {
        $p = $ev->getPlayer();
        $xuid = $p->getXuid();
        if (AccountManager::hasValue($xuid, 'wait_action')) {
            $ev->setCancelled();
            return;
        }

        switch ($ev->getItem()->getId()) {
            case ItemIds::STICK:
                FormManager::sendMenu($p);
                break;

            case ItemIds::CLOCK:
                if ($p->isSneaking()) {
                    if (AccountManager::hasValue($xuid, 'particle_cool_time')) return;
                    AccountManager::setValue($xuid, 'particle_cool_time', 20);
                    LandManager::$instance->checkSpace($p);
                } else {
                    if (AccountManager::hasValue($xuid, 'form_cool_time')) return;
                    AccountManager::setValue($xuid, 'form_cool_time', 10);
                    $p->sendForm(LandForm::landCreateAssistForm($xuid, $ev->getBlock()));
                }
                break;
        }
        $ev->setCancelled(LandManager::$instance->protect($p, $ev->getBlock(), null));
    }

    function onTeleport(EntityTeleportEvent $ev): void
    {
        $p = $ev->getEntity();
        if (!$p instanceof Player) return;
        if (AccountManager::hasValue($p->getXuid(), 'wait_action')) {
            $ev->setCancelled();
            return;
        }
        $fromLevelName = $ev->getFrom()->getLevel()->getFolderName();
        $toLevelName = $ev->getTo()->getLevel()->getFolderName();
        $isWorldChange = $fromLevelName !== $toLevelName;

        if (in_array($toLevelName, AccountManager::STOP_FLY_WORLD) && AccountManager::hasValue($p->getXuid(), 'fly')) {
            AccountManager::setValue($p->getXuid(), 'fly', 0);
            $p->setFlying(false);
            $p->setAllowFlight(false);
            $p->sendMessage('このワールドで飛行することはできません');
        }
        if ($isWorldChange) {
            unset(LandManager::$pos[$p->getXuid()]);
        }
    }

    /**
     * @priority MONITOR
     */
    function onEat(PlayerItemConsumeEvent $ev): void
    {
        $p = $ev->getPlayer();
        $food = $ev->getItem();
        $tag = $food->getNamedTagEntry("reef_infinite_food");
        if (!is_null($tag)) {
            CoralReefPlugin::$plugin->getScheduler()->scheduleDelayedTask(new ClosureTask(function (int $currentTick) use ($food, $p): void {
                $p->getInventory()->addItem($food->setCount(1));
            }), 3);
        }
    }

    /**
     * @priority MONITOR
     */
    function onTransactionMonitor(InventoryTransactionEvent $ev): void
    {
        $transaction = $ev->getTransaction();
        foreach ($transaction->getInventories() as $inv) {
            if ($inv instanceof ArmorInventory) { // 防具を動かしたとき、エフェクトの更新をする
                CoralReefPlugin::$plugin->getScheduler()->scheduleDelayedTask(new ClosureTask(function (int $currentTick) use ($transaction): void {
                    EffectTask::updateEffect($transaction->getSource());
                }), 3);
                return;
            }
        }
    }

    /**
     * @priority MONITOR
     */
    function onModeChangeMonitor(PlayerGameModeChangeEvent $ev): void
    {
        $p = $ev->getPlayer();
        if (AccountManager::hasValue($p->getXuid(), 'fly')) {
            AccountManager::setValue($p->getXuid(), 'fly', 0);
            $p->setFlying(false);
            $p->setAllowFlight(false);
            $p->sendMessage('モードが変わったため飛行を無効化しました');
        }
    }

    function onReceived(DataPacketReceiveEvent $ev)
    {
        $pk = $ev->getPacket();
        $p = $ev->getPlayer();
        if ($pk instanceof ItemFrameDropItemPacket) {
            $ev->setCancelled(LandManager::$instance->protect($p, new Position($pk->x, $pk->y, $pk->z, $p->getLevel()),
                'このワールドで額縁を変更することはできません'));
        }
    }
}
