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

namespace ree_jp\coral_reef\gatya\event;

use pocketmine\item\VanillaItems;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use poggit\libasynql\SqlError;
use ree_jp\coral_reef\gatya\GatyaManager;
use ree_jp\coral_reef\gatya\items\event\Christmas2021ReefItems;
use ree_jp\coral_reef\gatya\items\RareItems;
use ree_jp\coral_reef\gatya\items\SuperItems;
use ree_jp\coral_reef\sql\SQLConst;
use ree_jp\coral_reef\sql\SQLManager;

class Christmas2021
{
    static function gatya(SQLManager $repo, Player $p, int $number = 1): void
    {
        if ($number <= 0) return;
        $xuid = $p->getXuid();
        $repo->getLog($xuid, SQLConst::LOG_GATYA_CHRISTMAS_2021, function (array $rows) use ($repo, $number, $p, $xuid) {
            $isFirst = true;

            while ($resultLog = array_pop($rows)) {
                if ($resultLog["subtype"] === "reef_rare") {
                    $isFirst = false;
                    break;
                }
            }

            if ($number > 1) { // ガチャの処理が終了後に実行するClosure
                $func = function () use ($repo, $number, $p) {
                    self::gatya($repo, $p, --$number);
                };
            } else $func = null;

            $firstRand = mt_rand(1, 1000);

            switch (true) {
                case ($firstRand <= 5) || ($isFirst && ($firstRand <= 10)): // 0.5% (まだ1回も引いてない人は1%)
                    switch (mt_rand(1, 10)) {
                        case 1:
                        case 2:
                        case 3:
                            $item = Christmas2021ReefItems::getItem($xuid, Christmas2021ReefItems::PICKAXE);
                            break;
                        case 4:
                        case 5:
                        case 6:
                            $item = Christmas2021ReefItems::getItem($xuid, Christmas2021ReefItems::SHOVEL);
                            break;
                        case 7:
                        case 8:
                        case 9:
                            $item = Christmas2021ReefItems::getItem($xuid, Christmas2021ReefItems::AXE);
                            break;
                        case 10:
                            $item = Christmas2021ReefItems::getItem($xuid, Christmas2021ReefItems::HOE);
                            break;
                        default:
                            $p->sendMessage("エラーが発生しました");
                            return;
                    }
                    $percent = match ($isFirst) {
                        true => "1",
                        false => "0.5"
                    };
                    GatyaManager::gatyaProcess($repo, $p, SQLConst::TICKETS_CHRISTMAS_2021, 1, $item, "reef_rare",
                        TextFormat::GREEN . "REEFレア" . TextFormat::DARK_GRAY . "[$percent %]" . TextFormat::RESET, true, $func);
                    break;

                case $firstRand <= (5 + 150):// 15%
                    switch (mt_rand(1, 2)) {
                        case 1:
                            $item = SuperItems::getItem($xuid, SuperItems::PICKAXE);
                            break;
                        case 2:
                            $item = SuperItems::getItem($xuid, SuperItems::SHOVEL);
                            break;
                        default:
                            $p->sendMessage("エラーが発生しました");
                            return;
                    }
                    GatyaManager::gatyaProcess($repo, $p, SQLConst::TICKETS_CHRISTMAS_2021, 1, $item, "super_rare",
                        TextFormat::BLUE . "スーパーレア" . TextFormat::DARK_GRAY . "[15%]" . TextFormat::RESET, false, $func);
                    break;

                case $firstRand <= (155 + 345):// 34.5%
                    switch (mt_rand(1, 2)) {
                        case 1:
                            $item = RareItems::getItem($xuid, RareItems::PICKAXE);
                            break;
                        case 2:
                            $item = RareItems::getItem($xuid, RareItems::SHOVEL);
                            break;
                        default:
                            $p->sendMessage("エラーが発生しました");
                            return;
                    }
                    GatyaManager::gatyaProcess($repo, $p, SQLConst::TICKETS_CHRISTMAS_2021, 1, $item, "rare",
                        TextFormat::AQUA . "レア" . TextFormat::DARK_GRAY . "[34.5%]" . TextFormat::RESET, false, $func);
                    break;

                default:
                    $items = [VanillaItems::SNOWBALL()->setCount(8), VanillaItems::STEAK()->setCount(4),
                        VanillaItems::COOKED_CHICKEN()->setCount(4), VanillaItems::MUSHROOM_STEW(), VanillaItems::GOLDEN_APPLE()];
                    GatyaManager::gatyaProcess($repo, $p, SQLConst::TICKETS_CHRISTMAS_2021, 1, $items[array_rand($items)], "normal",
                        TextFormat::GOLD . "ノーマル" . TextFormat::DARK_GRAY . "[50%]" . TextFormat::RESET, false, $func);
                    break;
            }
        }, function (SqlError $error) use ($p) {
            $p->sendMessage("エラーが発生しました");
            Server::getInstance()->getLogger()->error("[Christmas2021GatyaCheckLimit] " . $p->getName() . "さんの処理中に" . $error->getErrorMessage());
        });
    }
}
