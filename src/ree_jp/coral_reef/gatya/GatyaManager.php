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
use pocketmine\item\Item;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use poggit\libasynql\SqlError;
use ree_jp\coral_reef\account\GiftData;
use ree_jp\coral_reef\account\GiftService;
use ree_jp\coral_reef\gatya\items\ReefItems;
use ree_jp\coral_reef\quest\QuestListener;
use ree_jp\coral_reef\sql\mysql\SQLRepository;
use ree_jp\coral_reef\sql\SQLConst;
use ree_jp\reef_edge\ReefEdgePlugin;
use ree_jp\reef_edge\socket\SocketService;

class GatyaManager
{
    static array $isProcessing = [];

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

    // チケットの枚数が足りるか確認してチケットを減らしてログに記録してアイテムを送る
    static function gatyaProcess(SQLRepository $repo, string $gatyaLog, Player $p, string $subtype, int $need, Item $item, string $rare, string $stringRare, bool $isBroadcast,
                                 ?Closure      $func, string $broadMessage = null): void
    {
        if (array_key_exists($p->getXuid(), self::$isProcessing)) {
            if ($p->isOnline()) {
                $p->sendMessage("ガチャを同時に実行することはできません");
            }
            return;
        }
        if ($broadMessage == null) {
            $broadMessage = $p->getDisplayName() . "さんが" . TextFormat::GREEN . "REEFレア" . TextFormat::RESET . "を引きました";
        }
        self::$isProcessing[$p->getXuid()] = true;
        // ガチャチケットが足りるか確認
        $repo->getValue($p->getXuid(), SQLConst::TYPE_TICKETS, $subtype,
            function (array $rows) use ($broadMessage, $repo, $gatyaLog, $stringRare, $func, $isBroadcast, $item, $rare, $subtype, $p, $need) {
                foreach ($rows as $row) {
                    if (isset($row['value']) && intval($row['value']) >= $need) {
                        // ログに追加
                        $repo->addLog($p->getXuid(), $gatyaLog, $rare,
                            $item->getNamedTag()->getString(ReefItems::REEF_SP_ITEM, 'unknown'), SQLConst::NOW_TIME,
                            function () use ($broadMessage, $repo, $stringRare, $func, $rare, $isBroadcast, $item, $need, $row, $subtype, $p) {
                                // ガチャチケットを減らす
                                $repo->setValue($p->getXuid(), SQLConst::TYPE_TICKETS, $subtype, $row['value'] - $need,
                                    function () use ($broadMessage, $repo, $stringRare, $func, $isBroadcast, $item, $p) {
                                        if ($p->isOnline()) {
                                            $p->sendMessage('ガチャを引きました(レア度: ' . TextFormat::GREEN . $stringRare . TextFormat::RESET . ')');
                                            if ($isBroadcast) {
                                                // 一定のレア度以上は$isBroadcastをtrueにしてガチャを引いたことを全体に表示させる
                                                SocketService::sendBroadcastMessage(ReefEdgePlugin::$socketClient, $broadMessage);
                                            }
                                        }

                                        if ($p->isOnline() && $p->getInventory()->canAddItem($item)) {
                                            // インベントリに空きがあれば追加
                                            $p->getInventory()->addItem($item);
                                        } else {
                                            // なければギフトに送信
                                            GiftService::addGift($repo, $p->getXuid(), new GiftData('0', 'ガチャ',
                                                time() + (7 * 24 * 60 * 60), [$item]),
                                                function () use ($p) {
                                                    if (!$p->isConnected()) return;
                                                    $p->sendMessage('ガチャの景品がインベントリに入れるスペースがなかったためギフトに送信しました');
                                                }, function () use ($item, $p) { // ギフト出来なければ落とす
                                                    if (!$p->isConnected()) return;
                                                    $p->dropItem($item);
                                                    $p->sendMessage('ガチャの景品を地面にドロップしました');
                                                });
                                        }

                                        unset(self::$isProcessing[$p->getXuid()]);
                                        QuestListener::callSubscribedQuest($p->getXuid(), QuestListener::GATYA, $item);
                                        if (!is_null($func) && $p->isOnline()) $func();
                                    }, function (SqlError $error) use ($p) {
                                        $p->sendMessage('エラーが発生しました');
                                        unset(self::$isProcessing[$p->getXuid()]);
                                        Server::getInstance()->getLogger()->error('[GatyaReduceTicket] ' . $p->getName() . 'さんの処理中に' . $error->getErrorMessage());
                                    });
                            }, function (SqlError $error) use ($p) {
                                $p->sendMessage('エラーが発生しました');
                                unset(self::$isProcessing[$p->getXuid()]);
                                Server::getInstance()->getLogger()->error('[GatyaLogAdd] ' . $p->getName() . 'さんの処理中に' . $error->getErrorMessage());
                            });
                    } else {
                        $p->sendMessage('ガチャチケットが足りません');
                        unset(self::$isProcessing[$p->getXuid()]);
                    }
                }
            }, function (SqlError $error) use ($p) {
                $p->sendMessage('エラーが発生しました');
                unset(self::$isProcessing[$p->getXuid()]);
                Server::getInstance()->getLogger()->error('[GatyaCheckTicket] ' . $p->getName() . 'さんの処理中に' . $error->getErrorMessage());
            });
    }
}
