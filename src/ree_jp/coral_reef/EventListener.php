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
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerGameModeChangeEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerPreLoginEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\item\Item;
use pocketmine\item\ItemIds;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use ree_jp\coral_reef\account\AccountManager;
use ree_jp\coral_reef\form\FormManager;
use ree_jp\coral_reef\sql\SQLManager;
use ree_jp\stackStorage\api\StackStorageAPI;

class EventListener implements Listener
{
    public function onPreLogin(PlayerPreLoginEvent $ev): void
    {
        $p = $ev->getPlayer();
        if (is_null(SQLManager::$manager)) {
            $p->kick(TextFormat::GREEN . 'Reef ' . TextFormat::YELLOW . 'Server' . TextFormat::DARK_RED . 'データベースサーバーが見つかりませんでした');
            return;
        }
        $reason = AccountManager::checkUser($p);
        if (is_string($reason)) {
            $isNotShow = strstr($reason, '[BAN_NOT_SHOW]', true);
            if ($isNotShow === false) {
                $p->kick("Banされています" . TextFormat::EOL . $reason);
            } else {
                $p->kick(TextFormat::GREEN . 'Reef ' . TextFormat::YELLOW . 'Server' . TextFormat::DARK_RED . "\n\n$isNotShow");
            }
        }
    }

    public function onJoin(PlayerJoinEvent $ev): void
    {
        $p = $ev->getPlayer();

        AccountManager::userJoin($p);

        if (is_null($p->getLastPlayed())) {
            $ev->setJoinMessage($p->getName() . 'さんが初めて参加しました');
        } else {
            if (AccountManager::hasValue($p->getXuid(), 'rejoin')) {
                $ev->setJoinMessage($p->getName() . 'さんが再参加しました');
            } else {
                $ev->setJoinMessage($p->getName() . 'さんが参加しました');
            }
        }
        if (!$p->getInventory()->contains(Item::get(ItemIds::STICK))) $p->getInventory()->addItem(Item::get(ItemIds::STICK)->setLore(['メニューを開きます']));
    }

    public function onQuit(PlayerQuitEvent $ev): void
    {
        $p = $ev->getPlayer();

        AccountManager::userQuit($p, $ev->getQuitReason());
        $ev->setQuitMessage(TextFormat::GRAY . $p->getName() . 'さんが' . $ev->getQuitReason() . 'で退出しました');
    }

    /**
     * @priority MONITOR
     */
    public function onBreakMonitor(BlockBreakEvent $ev): void
    {
        $p = $ev->getPlayer();
        if ($ev->isCancelled()) return;
        try {
            AccountManager::brockBroken($p, $ev->getBlock(), $ev->getItem());
        } catch (Exception $e) {
            $p->sendMessage('エラーが発生しました');
            Server::getInstance()->getLogger()->error('[blockBroke]' . $p->getName() . 'の処理中に' . $e->getMessage());
        }
        if (class_exists('StackStorageAPI')) {
            foreach ($ev->getDrops() as $dropItem) {
                StackStorageAPI::getInstance()->add($p->getXuid(), $dropItem);
            }
            $ev->setDrops([]);
        } else $p->sendMessage('ストレージ');
    }

    public function onTouch(PlayerInteractEvent $ev): void
    {
        $p = $ev->getPlayer();

        switch ($ev->getItem()->getId()) {
            case ItemIds::STICK:
                FormManager::sendMenu($p);
                break;
        }
    }

    public function onModeChange(PlayerGameModeChangeEvent $ev): void
    {
        $p = $ev->getPlayer();
        if (AccountManager::hasValue($p->getXuid(), 'fly')) {
            $p->setFlying(false);
            $p->setAllowFlight(false);
            $p->sendMessage('モードが変わったため飛行を無効化しました');
        }
    }
}
