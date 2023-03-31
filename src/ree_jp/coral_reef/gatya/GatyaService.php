<?php
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

use pocketmine\utils\TextFormat;
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
            "pick_up_image" => ["textures/items/reef_seichi_2022summer_pickaxe", "textures/items/reef_seichi_2022summer_axe",
                "textures/items/reef_seichi_2022summer_shovel", "textures/items/reef_seichi_2022summer_hoe"],
            "details" => "summer-event-only-gacha-2022",
            "ticket" => SQLConst::TICKETS_SUMMER_2022
        ],
        SQLConst::LOG_GATYA_HALLOWEEN_NIGHT => ["name" => TextFormat::GOLD . "Halloween" . TextFormat::DARK_PURPLE . "Night" . TextFormat::RESET . "ガチャ",
            "image" => "", "pick_up_image" => ["textures/items/halloween_night_pickaxe", "textures/items/halloween_night_axe",
                "textures/items/halloween_night_shovel", "textures/items/halloween_night_hoe"],
            "details" => "halloween-pickup-gacha-2022", "ticket" => SQLConst::TICKETS_NORMAL
        ],
        SQLConst::LOG_GATYA_HALLOWEEN_PARTY => ["name" => TextFormat::GOLD . "Halloween" . TextFormat::DARK_GREEN . "Party" . TextFormat::RESET . "ガチャ",
            "image" => "", "pick_up_image" => ["textures/items/halloween_party_pickaxe", "textures/items/halloween_party_axe",
                "textures/items/halloween_party_shovel", "textures/items/halloween_party_hoe"],
            "details" => "halloween-pickup-gacha-2022", "ticket" => SQLConst::TICKETS_NORMAL
        ],
        SQLConst::LOG_GATYA_SNOW_CANDY => ["name" => TextFormat::WHITE . "Snow" . TextFormat::RED . "Candy" . TextFormat::RESET . "ガチャ",
            "image" => "textures/ui/gatya/snow_candy_gatya", "pick_up_image" => ["textures/items/snow_candy_pickaxe", "textures/items/snow_candy_axe",
                "textures/items/snow_candy_shovel", "textures/items/snow_candy_hoe"],
            "details" => "snowcandy-gacha", "ticket" => SQLConst::TICKETS_CHRISTMAS_2022
        ],
        SQLConst::LOG_GATYA_APRIL_2023 => ["name" => TextFormat::GREEN . "Reef" . TextFormat::GOLD . "Sword" . TextFormat::RESET . "ガチャ",
            "image" => "textures/ui/gatya/reef_sword_gatya", "pick_up_image" => ["textures/items/reef_sword"],
            "details" => "reef-sword-gacha", "ticket" => SQLConst::TICKETS_NORMAL
        ]
    ];
    const TICKETS = [SQLConst::TICKETS_NORMAL => "ノーマルガチャチケット", SQLConst::TICKETS_CHRISTMAS_2021 => "クリスマスガチャチケット2021",
        SQLConst::TICKETS_SUMMER_2022 => "サマーガチャチケット2022", SQLConst::TICKETS_CHRISTMAS_2022 => "クリスマスガチャチケット2022"];

    static function ticketName(string $ticket): string
    {
        if (isset(self::TICKETS[$ticket])) return self::TICKETS[$ticket];
        return $ticket;
    }
}
