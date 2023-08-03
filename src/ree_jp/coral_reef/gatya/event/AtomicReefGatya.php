<?php /** @noinspection DuplicatedCode */

/*
 *  CCCCC                        lll RRRRRR                 fff
 * CC    C  oooo  rr rr    aa aa lll RR   RR   eee    eee  ff
 * CC      oo  oo rrr  r  aa aaa lll RRRRRR  ee   e ee   e ffff
 * CC    C oo  oo rr     aa  aaa lll RR  RR  eeeee  eeeee  ff
 *  CCCCC   oooo  rr      aaa aa lll RR   RR  eeeee  eeeee ff
 *
 * Copyright (c) 2023. Ree-jp(https://ree-jp.net)
 */

namespace ree_jp\coral_reef\gatya\event;

use Generator;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use ree_jp\coral_reef\gatya\GatyaManager;
use ree_jp\coral_reef\gatya\GatyaResult;
use ree_jp\coral_reef\gatya\GatyaService;
use ree_jp\coral_reef\gatya\items\event\AtomicReefItem;
use ree_jp\coral_reef\gatya\items\NormalItems;
use ree_jp\coral_reef\gatya\items\RareItems;
use ree_jp\coral_reef\gatya\items\SuperItems;
use ree_jp\coral_reef\gatya\items\UltimateItems;
use ree_jp\coral_reef\sql\SQLConst;
use ree_jp\reef_edge\ReefEdgePlugin;
use ree_jp\reef_edge\socket\SocketData;
use SOFe\AwaitGenerator\Await;

class AtomicReefGatya
{
    const GATYA_LOG = SQLConst::LOG_GATYA_ATOMIC;
    const TICKET_TYPE = SQLConst::TICKETS_SUMMER_2023;
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
        $trackId = $p->getXuid() . "-" . mt_rand(1, 999999);
        Await::f2c(function () use ($trackId, $number, $p): Generator {
            $trackMessage = "<trackId:$trackId>";

            $xuid = $p->getXuid();
            $lastReef = yield from GatyaService::getLastReef($p->getXuid(), self::GATYA_LOG);
            $results = [];

            $gatyaCount = 0;
            while ($gatyaCount < $number) {
                $gatyaCount++;
                $lastReef++;

                $result = self::getGatyaResult($xuid, $lastReef);
                if ($result->rare === self::REEF_RARE) {
                    $lastReef = 0;
                }
                $results[] = $result;
                $trackMessage .= "[$result->rare:$lastReef]";
            }
            GatyaManager::gatyaProcess($p, $results, self::GATYA_LOG, self::TICKET_TYPE, $trackId);

            ReefEdgePlugin::$socketClient->send(new SocketData("discord-message", ["message" => $trackMessage,
                "channelID" => "1136687962889916498"]));
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
                $item = match (mt_rand(1, 10)) {
                    1, 2, 3 => AtomicReefItem::getItem($xuid, AtomicReefItem::PICKAXE),

                    4, 5, 6 => AtomicReefItem::getItem($xuid, AtomicReefItem::SHOVEL),

                    7, 8, 9 => AtomicReefItem::getItem($xuid, AtomicReefItem::AXE),

                    10 => AtomicReefItem::getItem($xuid, AtomicReefItem::HOE),
                };
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
