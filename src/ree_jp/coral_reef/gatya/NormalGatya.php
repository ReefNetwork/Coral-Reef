<?php
/*
 *  CCCCC                        lll RRRRRR                 fff
 * CC    C  oooo  rr rr    aa aa lll RR   RR   eee    eee  ff
 * CC      oo  oo rrr  r  aa aaa lll RRRRRR  ee   e ee   e ffff
 * CC    C oo  oo rr     aa  aaa lll RR  RR  eeeee  eeeee  ff
 *  CCCCC   oooo  rr      aaa aa lll RR   RR  eeeee  eeeee ff
 *
 * Copyright (c) 2021. Ree-jp(https://ree-jp.net)
 */

namespace ree_jp\coral_reef\gatya;

use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use poggit\libasynql\SqlError;
use ree_jp\coral_reef\gatya\items\NormalItems;
use ree_jp\coral_reef\gatya\items\RareItems;
use ree_jp\coral_reef\gatya\items\ReefItems;
use ree_jp\coral_reef\gatya\items\SuperItems;
use ree_jp\coral_reef\gatya\items\UltimateItems;
use ree_jp\coral_reef\sql\mysql\SQLRepository;
use ree_jp\coral_reef\sql\SQLConst;

class NormalGatya
{
    static function gatya(SQLRepository $repo, Player $p, int $number = 1): void
    {
        if ($number <= 0) return;
        $xuid = $p->getXuid();
        $repo->getLog($xuid, SQLConst::LOG_GATYA, function (array $rows) use ($repo, $number, $p, $xuid) {
            $firstRand = mt_rand(1, 1000);
            $isLimit = true;
            for ($i = 0; $i < 100; $i++) { // 99回のガチャ履歴を調べてReefRareを引いてなかったら確定
                $resultLog = array_shift($rows);
                if (is_null($resultLog) || ($resultLog['subtype'] === 'reef_rare')) {
                    $isLimit = false;
                    break;
                }
            }
            if ($number > 1) { // ガチャの処理が終了後に実行するClosure
                $func = function () use ($repo, $number, $p) {
                    self::gatya($repo, $p, --$number);
                };
            } else $func = null;

            switch (true) {
                case ($firstRand <= 5) || $isLimit:// 0.5% or 天井
                    if (mt_rand(1, 10) < 5) { // 40%の確率でTool
                        switch (mt_rand(1, 10)) {
                            case 1:
                            case 2:
                            case 3:
                                $item = ReefItems::getItem($xuid, ReefItems::PICKAXE);
                                break;
                            case 4:
                            case 5:
                            case 6:
                                $item = ReefItems::getItem($xuid, ReefItems::SHOVEL);
                                break;
                            case 7:
                            case 8:
                            case 9:
                                $item = ReefItems::getItem($xuid, ReefItems::AXE);
                                break;
                            case 10:
                                $item = ReefItems::getItem($xuid, ReefItems::HOE);
                                break;
                            default:
                                $p->sendMessage('エラーが発生しました');
                                return;
                        }
                    } else { // 60%の確率で防具
                        switch (mt_rand(1, 4)) {
                            case 1:
                                $item = ReefItems::getItem($xuid, ReefItems::HELMET);
                                break;
                            case 2:
                                $item = ReefItems::getItem($xuid, ReefItems::CHEST_PLATE);
                                break;
                            case 3:
                                $item = ReefItems::getItem($xuid, ReefItems::LEGGINGS);
                                break;
                            case 4:
                                $item = ReefItems::getItem($xuid, ReefItems::BOOTS);
                                break;
                            default:
                                $p->sendMessage('エラーが発生しました');
                                return;
                        }
                    }
                    GatyaManager::gatyaProcess($repo, SQLConst::LOG_GATYA, $p, SQLConst::TICKETS_NORMAL, 1, $item, 'reef_rare',
                        TextFormat::GREEN . 'REEFレア' . TextFormat::DARK_GRAY . '[0.5%]' . TextFormat::RESET, true, $func);
                    break;

                case $firstRand <= (5 + 25):// 2.5%
                    switch (mt_rand(1, 3)) {
                        case 1:
                            $item = UltimateItems::getItem($xuid, UltimateItems::PICKAXE);
                            break;
                        case 2:
                            $item = UltimateItems::getItem($xuid, UltimateItems::AXE);
                            break;
                        case 3:
                            $item = UltimateItems::getItem($xuid, UltimateItems::SHOVEL);
                            break;
                        default:
                            $p->sendMessage('エラーが発生しました');
                            return;
                    }
                    GatyaManager::gatyaProcess($repo, SQLConst::LOG_GATYA, $p, SQLConst::TICKETS_NORMAL, 1, $item, 'ultimate_rare',
                        TextFormat::GOLD . 'ウルトラレア' . TextFormat::DARK_GRAY . '[2.5%]' . TextFormat::RESET, false, $func);
                    break;

                case $firstRand <= (30 + 100):// 10%
                    switch (mt_rand(1, 2)) {
                        case 1:
                            $item = SuperItems::getItem($xuid, SuperItems::PICKAXE);
                            break;
                        case 2:
                            $item = SuperItems::getItem($xuid, SuperItems::SHOVEL);
                            break;
                        default:
                            $p->sendMessage('エラーが発生しました');
                            return;
                    }
                    GatyaManager::gatyaProcess($repo, SQLConst::LOG_GATYA, $p, SQLConst::TICKETS_NORMAL, 1, $item, 'super_rare',
                        TextFormat::BLUE . 'スーパーレア' . TextFormat::DARK_GRAY . '[10%]' . TextFormat::RESET, false, $func);
                    break;

                case $firstRand <= (130 + 300):// 30%
                    switch (mt_rand(1, 2)) {
                        case 1:
                            $item = RareItems::getItem($xuid, RareItems::PICKAXE);
                            break;
                        case 2:
                            $item = RareItems::getItem($xuid, RareItems::SHOVEL);
                            break;
                        default:
                            $p->sendMessage('エラーが発生しました');
                            return;
                    }
                    GatyaManager::gatyaProcess($repo, SQLConst::LOG_GATYA, $p, SQLConst::TICKETS_NORMAL, 1, $item, 'rare',
                        TextFormat::AQUA . 'レア' . TextFormat::DARK_GRAY . '[30%]' . TextFormat::RESET, false, $func);
                    break;

                default:// 残り
                    $item = NormalItems::getItemInt($xuid, mt_rand(1, 7));
                    GatyaManager::gatyaProcess($repo, SQLConst::LOG_GATYA, $p, SQLConst::TICKETS_NORMAL, 1, $item, 'normal',
                        TextFormat::DARK_GRAY . 'ノーマル' . TextFormat::RESET, false, $func);
                    break;
            }
        }, function (SqlError $error) use ($p) {
            $p->sendMessage('エラーが発生しました');
            Server::getInstance()->getLogger()->error('[GatyaCheckLimit] ' . $p->getName() . 'さんの処理中に' . $error->getErrorMessage());
        });
    }
}
