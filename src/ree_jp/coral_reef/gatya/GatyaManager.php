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

namespace ree_jp\coral_reef\gatya;

use Closure;
use Generator;
use pocketmine\player\Player;
use pocketmine\Server;
use poggit\libasynql\SqlError;
use ree_jp\coral_reef\account\AccountStore;
use ree_jp\coral_reef\account\GiftData;
use ree_jp\coral_reef\account\GiftService;
use ree_jp\coral_reef\account\KVConst;
use ree_jp\coral_reef\CoralReefPlugin;
use ree_jp\coral_reef\gatya\items\ReefItems;
use ree_jp\coral_reef\sql\model\LogData;
use ree_jp\coral_reef\sql\mysql\SQLRepository;
use ree_jp\coral_reef\sql\repo\LogRepository;
use ree_jp\coral_reef\sql\SQLConst;
use ree_jp\coral_reef\StoreHouse;
use ree_jp\reef_edge\ReefEdgePlugin;
use ree_jp\reef_edge\socket\SocketService;
use SOFe\AwaitGenerator\Await;

class GatyaManager
{

    static function addTicket(SQLRepository $repo, string $xuid, string $type, int $count, ?Closure $func = null): void
    {
        $repo->addValue($xuid, SQLConst::TYPE_TICKETS, $type, $count, $func, function (SqlError $error) use ($xuid): void {
            Server::getInstance()->getLogger()->error('[TicketAdd] ' . $xuid . 'さんの処理中に' . $error->getErrorMessage());
        });
    }

    static function setTicket(SQLRepository $repo, string $xuid, string $type, int $count): void
    {
        $repo->setValue($xuid, SQLConst::TYPE_TICKETS, $type, strval($count), null,
            function (SqlError $error) use ($xuid): void {
                Server::getInstance()->getLogger()->error('[TicketSet] ' . $xuid . 'さんの処理中に' . $error->getErrorMessage());
            });
    }

    /**
     * @param Player $p
     * @param GatyaResult[] $gatyaResults
     * @param string $gatyaLog
     * @param string $ticketType
     * @param string $trackId
     * @return void
     */
    static function gatyaProcess(Player $p, array $gatyaResults, string $gatyaLog, string $ticketType, string $trackId = ""): void
    {
        $reefCount = 0;
        foreach ($gatyaResults as $result) {
            if ($result->broadcastMessage != null) $reefCount++;
        }
        if ($reefCount >= 2) {
            /** @var AccountStore $store */
            $store = StoreHouse::$instance->get(AccountStore::class);
            $message = "[ガチャシステムエラー?報告]\n";
            $message .= "user:" . $p->getXuid() . "\n";
            $message .= "now processing:" . $store->hasValue($p->getXuid(), KVConst::GATYA_PROCESSING) . "\n";
            $message .= "type:" . $gatyaLog . ":" . $ticketType . ":" . $reefCount . "\n";
            $message .= "count:" . count($gatyaResults) . "\n";
            $message .= "trackId:" . $trackId . "\n";

            SocketService::sendBroadcastMessage(ReefEdgePlugin::$socketClient, $message);
        }

        $count = count($gatyaResults);
        /** @var SQLRepository $sqlRepo */
        $sqlRepo = CoralReefPlugin::$plugin->pool->get(SQLRepository::class);

        $sqlRepo->getValue($p->getXuid(), SQLConst::TYPE_TICKETS, $ticketType, function (array $rows) use ($ticketType, $gatyaLog, $gatyaResults, $sqlRepo, $p, $count): void {
            if (!$p->isOnline()) return;


            $xuid = $p->getXuid();
            $row = current($rows);
            if ($row && isset($row["value"]) && intval($row["value"]) >= $count) {
                /** @var LogRepository $logRepo */
                $logRepo = CoralReefPlugin::$plugin->pool->get(LogRepository::class);

                foreach ($gatyaResults as $result) {
                    Await::f2c(function () use ($sqlRepo, $p, $gatyaLog, $xuid, $result, $logRepo): Generator {
                        $logValue = $result->item->getNamedTag()->getString(ReefItems::REEF_SP_ITEM, "unknown");
                        yield from $logRepo->addLog(LogData::create($xuid, $gatyaLog, $result->rare, $logValue));

                        if ($p->isOnline()) {
                            $p->sendMessage("ガチャを引きました| " . $result->message);
                            if ($result->broadcastMessage != null) {
                                SocketService::sendBroadcastMessage(ReefEdgePlugin::$socketClient, "§6-§r" . $p->getDisplayName() . "§6-§r " . $result->broadcastMessage);
                            }
                        }

                        if ($p->isOnline() && $p->getInventory()->canAddItem($result->item)) {
                            $p->getInventory()->addItem($result->item);
                        } else {
                            GiftService::addGift($sqlRepo, $p->getXuid(), new GiftData("0", "ガチャ",
                                time() + (7 * 24 * 60 * 60), [$result->item]),
                                function () use ($p) {
                                    if (!$p->isConnected()) return;
                                    $p->sendMessage("ガチャの景品がインベントリに入れるスペースがなかったためギフトに送信しました");
                                }, function () use ($result, $p) { // ギフト出来なければ落とす
                                    if (!$p->isConnected()) return;
                                    $p->dropItem($result->item);
                                    $p->sendMessage("ガチャの景品を地面にドロップしました");
                                });
                        }
                    });
                }

                $sqlRepo->setValue($xuid, SQLConst::TYPE_TICKETS, $ticketType, $row["value"] - $count, function () use ($xuid): void {
                    /** @var AccountStore $store */
                    $store = StoreHouse::$instance->get(AccountStore::class);
                    $store->setValue($xuid, KVConst::GATYA_PROCESSING, 0);
                }, function () use ($count, $xuid): void {
                    SocketService::sendBroadcastMessage(ReefEdgePlugin::$socketClient, "[gatya error]" . $xuid . "|" . $count);
                });
            } else {
                $p->sendMessage("ガチャチケットが足りません");
            }
        }, function () use ($p): void {
            if (!$p->isOnline()) return;

            $p->sendMessage("ガチャの準備中にエラーが発生しました");
        });

    }
}
