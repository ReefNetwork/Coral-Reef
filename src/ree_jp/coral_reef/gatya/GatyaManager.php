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
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use poggit\libasynql\SqlError;
use ree_jp\coral_reef\account\GiftData;
use ree_jp\coral_reef\account\GiftManager;
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
            if ($number > 1) { // ガチャの処理が終了後に実行するClosure
                $func = function () use ($number, $p) {
                    self::normalGatya($p, --$number);
                };
            } else $func = null;

            switch (true) {
                case ($firstRand <= 5) || $isLimit:// 0.5% or 天井
                    switch (mt_rand(1, 3)) {
                        case 1:
                            $item = ReefItems::getItem($xuid, ReefItems::PICKAXE);
                            break;
                        case 2:
                            $item = ReefItems::getItem($xuid, ReefItems::AXE);
                            break;
                        case 3:
                            $item = ReefItems::getItem($xuid, ReefItems::HOE);
                            break;
                        default:
                            $p->sendMessage('エラーが発生しました');
                            return;
                    }
                    self::reduceTicket($p, SQLConst::TICKETS_NORMAL, 1, $item, 'reef_rare',
                        TextFormat::GREEN . 'REEFレア' . TextFormat::DARK_GRAY . '[0.5%]' . TextFormat::RESET, true, $func);
                    break;

                case $firstRand <= (5 + 25):// 2.5%
                    switch (mt_rand(1, 3)) {
                        case 1:
                            $item = UltimateItems::getItem($xuid, ReefItems::PICKAXE);
                            break;
                        case 2:
                            $item = UltimateItems::getItem($xuid, ReefItems::AXE);
                            break;
                        case 3:
                            $item = UltimateItems::getItem($xuid, ReefItems::SHOVEL);
                            break;
                        default:
                            $p->sendMessage('エラーが発生しました');
                            return;
                    }
                    self::reduceTicket($p, SQLConst::TICKETS_NORMAL, 1, $item, 'ultimate_rare',
                        TextFormat::GOLD . 'ウルトラレア' . TextFormat::DARK_GRAY . '[2.5%]' . TextFormat::RESET, false, $func);
                    break;

                case $firstRand <= (30 + 100):// 10%
                    switch (mt_rand(1, 2)) {
                        case 1:
                            $item = SuperItems::getItem($xuid, ReefItems::PICKAXE);
                            break;
                        case 2:
                            $item = SuperItems::getItem($xuid, ReefItems::SHOVEL);
                            break;
                        default:
                            $p->sendMessage('エラーが発生しました');
                            return;
                    }
                    self::reduceTicket($p, SQLConst::TICKETS_NORMAL, 1, $item, 'super_rare',
                        TextFormat::BLUE . 'スーパーレア' . TextFormat::DARK_GRAY . '[10%]' . TextFormat::RESET, false, $func);
                    break;

                case $firstRand <= (130 + 300):// 30%
                    switch (mt_rand(1, 2)) {
                        case 1:
                            $item = RareItems::getItem($xuid, ReefItems::PICKAXE);
                            break;
                        case 2:
                            $item = RareItems::getItem($xuid, ReefItems::SHOVEL);
                            break;
                        default:
                            $p->sendMessage('エラーが発生しました');
                            return;
                    }
                    self::reduceTicket($p, SQLConst::TICKETS_NORMAL, 1, $item, 'rare',
                        TextFormat::AQUA . 'レア' . TextFormat::DARK_GRAY . '[30%]' . TextFormat::RESET, false, $func);
                    break;

                default:// 残り
                    $item = NormalItems::getItem($xuid, mt_rand(1, 7));
                    self::reduceTicket($p, SQLConst::TICKETS_NORMAL, 1, $item, 'normal',
                        TextFormat::DARK_GRAY . 'ノーマル' . TextFormat::RESET, false, $func);
                    break;
            }
        }, function (SqlError $error) use ($p) {
            $p->sendMessage('エラーが発生しました');
            Server::getInstance()->getLogger()->error('[GatyaCheckLimit] ' . $p->getName() . 'さんの処理中に' . $error->getErrorMessage());
        });
    }

    private static function reduceTicket(Player $p, string $subtype, int $need, Item $item, string $rare, string $stringRare, bool $isBroadcast, ?Closure $func): void
    {
        // ガチャチケットが足りるか確認
        SQLManager::$manager->getValue($p->getXuid(), SQLConst::TYPE_TICKETS, $subtype,
            function (array $rows) use ($stringRare, $func, $isBroadcast, $item, $rare, $subtype, $p, $need) {
                $row = array_shift($rows);
                if (isset($row['value']) && intval($row['value']) >= $need) {
                    // ログに追加
                    SQLManager::$manager->addLog($p->getXuid(), SQLConst::LOG_GATYA, $rare,
                        $item->getNamedTag()->getString(ReefItems::REEF_SP_ITEM, 'unknown'), SQLConst::NOW_TIME,
                        function (int $insertId, int $affectedRows) use ($stringRare, $func, $rare, $isBroadcast, $item, $need, $row, $subtype, $p) {
                            // ガチャチケットを減らす
                            SQLManager::$manager->setValue($p->getXuid(), SQLConst::TYPE_TICKETS, $subtype, $row['value'] - $need,
                                function () use ($stringRare, $func, $isBroadcast, $item, $p) {
                                    if ($p->getInventory()->canAddItem($item)) { // インベントリに空きがあれば追加
                                        $p->getInventory()->addItem($item);
                                    } else { // なければギフトに送信
                                        GiftManager::addGift($p->getXuid(), new GiftData('0', 'ノーマルガチャ',
                                            time() + (7 * 24 * 60 * 60), [$item]),
                                            function () use ($p) {
                                                $p->sendMessage('ガチャの景品がインベントリに入れるスペースがなかったためプレゼントに送信しました');
                                            }, function (SqlError $error) use ($item, $p) { // ギフト出来なければ落とす
                                                $p->dropItem($item);
                                                $p->sendMessage('ガチャの景品を地面にドロップしました');
                                            });
                                    }
                                    $p->sendMessage('ガチャを引きました(レア度: ' . TextFormat::GREEN . $stringRare . TextFormat::RESET . ')');
                                    if ($isBroadcast) { // 一定のレア度以上は$isBroadcastをtrueにしてガチャを引いたことを全体に表示させる
                                        $broadMessage = $p->getDisplayName() . 'さんが' . TextFormat::GREEN . 'REEFレア' . TextFormat::RESET . 'を引きました';
                                        Server::getInstance()->broadcastMessage($broadMessage);
                                        CoralReefPlugin::$plugin->discordBot->sendChat($broadMessage);
                                    }
                                    $func();
                                }, function (SqlError $error) use ($p) {
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
