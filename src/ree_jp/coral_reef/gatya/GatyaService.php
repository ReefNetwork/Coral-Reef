<?php /** @noinspection ALL */

/*
 *  CCCCC                        lll RRRRRR                 fff
 * CC    C  oooo  rr rr    aa aa lll RR   RR   eee    eee  ff
 * CC      oo  oo rrr  r  aa aaa lll RRRRRR  ee   e ee   e ffff
 * CC    C oo  oo rr     aa  aaa lll RR  RR  eeeee  eeeee  ff
 *  CCCCC   oooo  rr      aaa aa lll RR   RR  eeeee  eeeee ff
 *
 * Copyright (c) 2022. Ree-jp(https://ree-jp.net)
 */

namespace ree_jp\coral_reef\gatya;

use Generator;
use pocketmine\utils\TextFormat;
use ree_jp\coral_reef\CoralReefPlugin;
use ree_jp\coral_reef\sql\model\LogData;
use ree_jp\coral_reef\sql\repo\LogRepository;
use ree_jp\coral_reef\sql\SQLConst;

class GatyaService
{
    const GATYA = [
        SQLConst::LOG_GATYA => [
            "name" => "ノーマルガチャ",
            "image" => "textures/ui/gatya/normal_gatya",
            "pick_up_image" => ["textures/items/reef_pickaxe", "textures/items/reef_axe", "textures/items/reef_shovel", "textures/items/reef_hoe"],
            "details" => "norma-gacha",
            "ticket" => SQLConst::TICKETS_NORMAL
        ],
        SQLConst::LOG_GATYA_CHRISTMAS_2021 => [
            "name" => "クリスマスガチャ2021", "image" => "",
            "pick_up_image" => ["textures/items/reef_pickaxe", "textures/items/reef_axe", "textures/items/reef_shovel", "textures/items/reef_hoe"],
            "details" => "reefserver-christmas2021",
            "ticket" => SQLConst::TICKETS_CHRISTMAS_2021
        ],
        SQLConst::LOG_GATYA_SUMMER_2022 => [
            "name" => "サマーガチャ2022", "image" => "",
            "pick_up_image" => ["textures/items/summer2022/pickaxe", "textures/items/summer2022/axe",
                "textures/items/summer2022/shovel", "textures/items/summer2022/hoe"],
            "details" => "summer-event-only-gacha-2022",
            "ticket" => SQLConst::TICKETS_SUMMER_2022
        ],
        SQLConst::LOG_GATYA_HALLOWEEN_NIGHT => ["name" => TextFormat::GOLD . "Halloween" . TextFormat::DARK_PURPLE . "Night" . TextFormat::RESET . "ガチャ",
            "image" => "", "pick_up_image" => ["textures/items/halloween_night/pickaxe", "textures/items/halloween_night/axe",
                "textures/items/halloween_night/shovel", "textures/items/halloween_night/hoe"],
            "details" => "halloween-pickup-gacha-2022", "ticket" => SQLConst::TICKETS_NORMAL
        ],
        SQLConst::LOG_GATYA_HALLOWEEN_PARTY => ["name" => TextFormat::GOLD . "Halloween" . TextFormat::DARK_GREEN . "Party" . TextFormat::RESET . "ガチャ",
            "image" => "", "pick_up_image" => ["textures/items/halloween_party/pickaxe", "textures/items/halloween_party/axe",
                "textures/items/halloween_party/shovel", "textures/items/halloween_party/hoe"],
            "details" => "halloween-pickup-gacha-2022", "ticket" => SQLConst::TICKETS_NORMAL
        ],
        SQLConst::LOG_GATYA_SNOW_CANDY => ["name" => TextFormat::WHITE . "Snow" . TextFormat::RED . "Candy" . TextFormat::RESET . "ガチャ",
            "image" => "textures/ui/gatya/snow_candy_gatya", "pick_up_image" => ["textures/items/snow_candy/pickaxe", "textures/items/snow_candy/axe",
                "textures/items/snow_candy/shovel", "textures/items/snow_candy/hoe"],
            "details" => "snowcandy-gacha", "ticket" => SQLConst::TICKETS_CHRISTMAS_2022
        ],
        SQLConst::LOG_GATYA_APRIL_2023 => ["name" => TextFormat::GREEN . "Reef" . TextFormat::GOLD . "Sword" . TextFormat::RESET . "ガチャ",
            "image" => "textures/ui/gatya/reef_sword_gatya", "pick_up_image" => ["textures/items/reef_sword"],
            "details" => "reef-sword-gacha", "ticket" => SQLConst::TICKETS_NORMAL
        ],
        SQLConst::LOG_GATYA_STEAM_PUNK => ["name" => TextFormat::GRAY . "Steam" . TextFormat::WHITE . "Punk" . TextFormat::RESET . "ガチャ",
            "image" => "textures/ui/gatya/steam_punk_gatya", "pick_up_image" => ["textures/items/steam_punk/pickaxe", "textures/items/steam_punk/axe",
                "textures/items/steam_punk/shovel", "textures/items/steam_punk/hoe"],
            "details" => "steampunk-gacha", "ticket" => SQLConst::TICKETS_NORMAL
        ],
        SQLConst::LOG_GATYA_SUMMER_2022_RE_1 => [
            "name" => "サマーガチャ2022復刻(1)", "image" => "textures/ui/gatya/summer2022gatya",
            "pick_up_image" => ["textures/items/summer2022/pickaxe", "textures/items/summer2022/axe",
                "textures/items/summer2022/shovel", "textures/items/summer2022/hoe"],
            "details" => "reinstatting-summer-event-limited-gacha-2022",
            "ticket" => SQLConst::TICKETS_NORMAL
        ],
        SQLConst::LOG_GATYA_ATOMIC => ["name" => TextFormat::AQUA . "Atomic" . TextFormat::GREEN . "Reef" . TextFormat::RESET . "ガチャ",
            "image" => "textures/ui/gatya/atomic_gatya", "pick_up_image" => ["textures/items/atomic/pickaxe", "textures/items/atomic/axe",
                "textures/items/atomic/shovel", "textures/items/atomic/hoe"],
            "details" => "atomicreef-gachcha", "ticket" => SQLConst::TICKETS_SUMMER_2023
        ]
    ];
    const TICKETS = [SQLConst::TICKETS_NORMAL => "ノーマルガチャチケット", SQLConst::TICKETS_CHRISTMAS_2021 => "クリスマスガチャチケット2021",
        SQLConst::TICKETS_SUMMER_2022 => "サマーガチャチケット2022", SQLConst::TICKETS_CHRISTMAS_2022 => "クリスマスガチャチケット2022",
        SQLConst::TICKETS_SUMMER_2023 => "サマーガチャチケット2023"];

    static function ticketName(string $ticket): string
    {
        if (isset(self::TICKETS[$ticket])) return self::TICKETS[$ticket];
        return $ticket;
    }

    // 最後に引いたReefが何回前か
    static function getLastReef(string $xuid, string $type, int $limit = 100): Generator
    {
        /** @var $repo LogRepository */
        $repo = CoralReefPlugin::$plugin->pool->get(LogRepository::class);
        /** @var LogData[] $logs */
        $logs = yield from $repo->getLogNewer($xuid, $type);
        for ($i = 0; $i < $limit; $i++) { // 99回のガチャ履歴を調べてReefRareを引いてなかったら確定
            $log = array_shift($logs);

            if (is_null($log) || ($log->subtype === "reef_rare")) {
                return $i;
            }
        }
        return $limit;
    }
}
