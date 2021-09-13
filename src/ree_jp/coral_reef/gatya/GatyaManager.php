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
use pocketmine\item\ItemIds;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use poggit\libasynql\SqlError;
use ree_jp\coral_reef\CoralReefPlugin;
use ree_jp\coral_reef\sql\SQLConst;
use ree_jp\coral_reef\sql\SQLManager;

class GatyaManager
{
    static function normalGatya(Player $p, int $number = 1): void
    {
        if ($number <= 0) return;
        $xuid = $p->getXuid();
        SQLManager::$manager->getLog($xuid, SQLConst::LOG_GATYA, function (array $rows) use ($number, $p, $xuid) {
            $firstRand = mt_rand(1, 1000);
            $isLimit = true;
            for ($i = 0; $i < 100; $i++) { // 99回のガチャ履歴を調べてReefRareを引いてなかったら確定
                $resultLog = array_pop($rows);
                if (is_null($resultLog) || ($resultLog['subtype'] === 'ReefRare')) {
                    $isLimit = false;
                    break;
                }
            }

            switch (true) {
                case ($firstRand <= 5) || $isLimit:// 0.5% or 天井
                    switch (mt_rand(1, 4)) {
                        case 1:
                            $item = ReefTools::getReef($xuid, ReefTools::PICKAXE);
                            $itemDescription = 'reef_pickaxe';
                            break;
                        case 2:
                            $item = Item::get(ItemIds::GOLDEN_AXE, ReefTools::AXE);
                            $itemDescription = 'reef_axe';
                            break;
                        case 3:
                            $item = Item::get(ItemIds::GOLDEN_HOE, ReefTools::HOE);
                            $itemDescription = 'reef_hoe';
                            break;
                        default:
                            $p->sendMessage('エラーが発生しました');
                            return;
                    }

                    self::reduceTicket($p, SQLConst::TICKETS_NORMAL, 1, 'ReefRare', $itemDescription, function () use ($item, $number, $p) {
                        if ($p->getInventory()->canAddItem($item)) {
                            $p->getInventory()->addItem($item);
                        } else // TODO
                            $p->sendMessage('ガチャを引きました(レア度: ' . TextFormat::GREEN . 'REEFレア' . TextFormat::RESET . ')');
                        $broadMessage = $p->getDisplayName() . 'さんが' . TextFormat::GREEN . 'REEFレア' . TextFormat::RESET . 'を引きました';
                        Server::getInstance()->broadcastMessage($broadMessage);
                        CoralReefPlugin::$plugin->discordBot->sendChat($broadMessage);
                        if ($number > 1) self::normalGatya($p, --$number);
                    });
                    break;

                case $firstRand <= (5 + 25):// 2.5%
                    self::reduceTicket($p, SQLConst::TICKETS_NORMAL, 1, 'UltimateRare', '', function () use ($number, $p) {
                        $p->sendMessage('ガチャを引きました(レア度: ' . TextFormat::GOLD . 'ウルトラレア[2.5%]' . TextFormat::RESET . ')');
                        if ($number > 1) self::normalGatya($p, --$number);
                    });
                    break;

                case $firstRand <= (30 + 100):// 10%
                    self::reduceTicket($p, SQLConst::TICKETS_NORMAL, 1, 'SuperRare', '', function () use ($number, $p) {
                        $p->sendMessage('ガチャを引きました(レア度: ' . TextFormat::BLUE . 'スーパーレア[10%]' . TextFormat::RESET . ')');
                        if ($number > 1) self::normalGatya($p, --$number);
                    });
                    break;

                case $firstRand <= (130 + 300):// 30%
                    self::reduceTicket($p, SQLConst::TICKETS_NORMAL, 1, 'Rare', '', function () use ($number, $p) {
                        $p->sendMessage('ガチャを引きました(レア度: ' . TextFormat::AQUA . 'レア[30%]' . TextFormat::RESET . ')');
                        if ($number > 1) self::normalGatya($p, --$number);
                    });
                    break;

                default:// 残り
                    self::reduceTicket($p, SQLConst::TICKETS_NORMAL, 1, 'Normal', '', function () use ($number, $p) {
                        $p->sendMessage('ガチャを引きました(レア度: ' . TextFormat::DARK_GRAY . 'ノーマル' . TextFormat::RESET . ')');
                        if ($number > 1) self::normalGatya($p, --$number);
                    });
                    break;
            }
        }, function (SqlError $error) use ($p) {
            $p->sendMessage('エラーが発生しました');
            Server::getInstance()->getLogger()->error('[GatyaCheckLimit] ' . $p->getName() . 'さんの処理中に' . $error->getErrorMessage());
        });
    }

    private static function reduceTicket(Player $p, string $subtype, int $need, string $rare, string $logValue, Closure $func): void
    {
        // ガチャチケットが足りるか確認
        SQLManager::$manager->getValue($p->getXuid(), SQLConst::TYPE_TICKETS, $subtype,
            function (array $rows) use ($logValue, $rare, $func, $subtype, $p, $need) {
                $row = array_shift($rows);
                if (isset($row['value']) && intval($row['value']) >= $need) {
                    // ログに追加
                    SQLManager::$manager->addLog($p->getXuid(), SQLConst::LOG_GATYA, $rare, $logValue, SQLConst::NOW_TIME,
                        function (int $insertId, int $affectedRows) use ($func, $need, $row, $subtype, $p) {
                            // ガチャチケットを減らす
                            SQLManager::$manager->setValue($p->getXuid(), SQLConst::TYPE_TICKETS, $subtype, $row['value'] - $need, $func,
                                function (SqlError $error) use ($p) {
                                    $p->sendMessage('エラーが発生しました');
                                    Server::getInstance()->getLogger()->error('[GatyaReduceTicket] ' . $p->getName() . 'さんの処理中に' . $error->getErrorMessage());
                                });
                        }, function (SqlError $error) use ($p) {
                            $p->sendMessage('エラーが発生しました');
                            Server::getInstance()->getLogger()->error('[GatyaLogAdd] ' . $p->getName() . 'さんの処理中に' . $error->getErrorMessage());
                        });
                }
                $p->sendMessage('チケットが足りません');
            }, function (SqlError $error) use ($p) {
                $p->sendMessage('エラーが発生しました');
                Server::getInstance()->getLogger()->error('[GatyaCheckTicket] ' . $p->getName() . 'さんの処理中に' . $error->getErrorMessage());
            });
    }
}
