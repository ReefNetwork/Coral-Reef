<?php /** @noinspection ALL */

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

use Generator;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use ree_jp\coral_reef\gatya\items\event\HalloweenNightItems;
use ree_jp\coral_reef\gatya\items\event\HalloweenPartyItems;
use ree_jp\coral_reef\gatya\items\NormalItems;
use ree_jp\coral_reef\gatya\items\RareItems;
use ree_jp\coral_reef\gatya\items\ReefItems;
use ree_jp\coral_reef\gatya\items\SuperItems;
use ree_jp\coral_reef\gatya\items\UltimateItems;
use ree_jp\coral_reef\sql\SQLConst;
use SOFe\AwaitGenerator\Await;

class NormalGatya
{
    const GATYA_LOG = SQLConst::LOG_GATYA;
    const TICKET_TYPE = SQLConst::TICKETS_NORMAL;
    const PROBABILITY = [
        self::REEF_RARE => 5, // 0.5%
        self::ULTIMATE_RARE => 25, // 2.5%
        self::SUPER_RARE => 100, // 10%
        self::RARE => 300, // 30%
        self::NORMAL => 570 // 100 - (0.5+2.5+10+30) = 57%
    ];

    const REEF_RARE = "reef_rare";
    const ULTIMATE_RARE = "ultimate_rare";
    const SUPER_RARE = "super_rare";
    const RARE = "rare";
    const NORMAL = "normal";

    static function gatya(Player $p, int $number = 1): void
    {
        Await::f2c(function () use ($number, $p): Generator {
            $xuid = $p->getXuid();
            $lastReef = yield from GatyaService::getLastReef($p->getXuid(), self::GATYA_LOG);
            $results = [];

            $gatyaCount = 0;
            while ($gatyaCount < $number) {
                $gatyaCount++;
                $lastReef++;

                $result = self::getGatyaResult($xuid, $lastReef);
                if ($result->rare === self::REEF_RARE) {
                    var_dump("last reef reset");
                    $lastReef = 0;
                }
                $results[] = $result;
            }
            GatyaManager::gatyaProcess($p, $results, self::GATYA_LOG, self::TICKET_TYPE);
        });
    }

    private static function getGatyaResult(string $xuid, int $lastReef): GatyaResult
    {
        $rarity = null;
        $percent = "";
        if ($lastReef >= 100) {
            $rarity = self::REEF_RARE;
            $percent = "天井";
        } else {
            $result_number = mt_rand(1, array_sum(self::PROBABILITY));// 1~100
            $total_number = 0;
            foreach (self::PROBABILITY as $rare => $probability) {
                $total_number += $probability;
                if ($result_number <= $total_number) {
                    $rarity = $rare;
                    $percent = ($probability / array_sum(self::PROBABILITY)) * 100;
                    break;
                }
            }
        }
        $percent = TextFormat::DARK_GRAY . "(" . $percent . "%)" . TextFormat::RESET;

        switch ($rarity) {
            case self::REEF_RARE:
                if (mt_rand(1, 10) < 5) { // 40%の確率でTool
                    $item = match (mt_rand(1, 10)) {
                        1, 2, 3 => match (mt_rand(1, 3)) {
                            1 => ReefItems::getItem($xuid, ReefItems::PICKAXE),
                            2 => HalloweenNightItems::getItem($xuid, HalloweenNightItems::PICKAXE),
                            3 => HalloweenPartyItems::getItem($xuid, HalloweenPartyItems::PICKAXE),
                        },

                        4, 5, 6 => match (mt_rand(1, 3)) {
                            1 => ReefItems::getItem($xuid, ReefItems::SHOVEL),
                            2 => HalloweenNightItems::getItem($xuid, HalloweenNightItems::SHOVEL),
                            3 => HalloweenPartyItems::getItem($xuid, HalloweenPartyItems::SHOVEL),
                        },

                        7, 8, 9 => match (mt_rand(1, 3)) {
                            1 => ReefItems::getItem($xuid, ReefItems::AXE),
                            2 => HalloweenNightItems::getItem($xuid, HalloweenNightItems::AXE),
                            3 => HalloweenPartyItems::getItem($xuid, HalloweenPartyItems::AXE),
                        },

                        10 => match (mt_rand(1, 3)) {
                            1 => ReefItems::getItem($xuid, ReefItems::HOE),
                            2 => HalloweenNightItems::getItem($xuid, HalloweenNightItems::HOE),
                            3 => HalloweenPartyItems::getItem($xuid, HalloweenPartyItems::HOE),
                        },
                    };
                } else { // 60%の確率で防具
                    $item = match (mt_rand(1, 4)) {
                        1 => ReefItems::getItem($xuid, ReefItems::HELMET),
                        2 => ReefItems::getItem($xuid, ReefItems::CHEST_PLATE),
                        3 => ReefItems::getItem($xuid, ReefItems::LEGGINGS),
                        4 => ReefItems::getItem($xuid, ReefItems::BOOTS),
                    };
                }
                $broadMessage = TextFormat::GREEN . "REEFレア" . TextFormat::RESET . "を引きました" . $percent;
                return new GatyaResult($rarity, TextFormat::GREEN . "REEFレア" . $percent, $item, $broadMessage);

            case self::ULTIMATE_RARE:// 2.5%
                $item = match (mt_rand(1, 3)) {
                    1 => UltimateItems::getItem($xuid, UltimateItems::PICKAXE),
                    2 => UltimateItems::getItem($xuid, UltimateItems::AXE),
                    3 => UltimateItems::getItem($xuid, UltimateItems::SHOVEL),
                };
                return new GatyaResult($rarity, TextFormat::GOLD . "ウルトラレア" . $percent, $item);

            case self::SUPER_RARE:// 10%
                $item = match (mt_rand(1, 2)) {
                    1 => SuperItems::getItem($xuid, SuperItems::PICKAXE),
                    2 => SuperItems::getItem($xuid, SuperItems::SHOVEL),
                };
                return new GatyaResult($rarity, TextFormat::BLUE . "スーパーレア" . $percent, $item);

            case self::RARE:// 30%
                $item = match (mt_rand(1, 2)) {
                    1 => RareItems::getItem($xuid, RareItems::PICKAXE),
                    2 => RareItems::getItem($xuid, RareItems::SHOVEL),
                };
                return new GatyaResult($rarity, TextFormat::AQUA . "レア" . $percent, $item);

            default:// 残り
                $item = NormalItems::getItemInt($xuid, mt_rand(1, 7));
                return new GatyaResult($rarity, TextFormat::DARK_GRAY . "ノーマル" . $percent, $item);
        }
    }
}
