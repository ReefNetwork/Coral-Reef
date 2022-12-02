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

class GatyaConst
{
    const GATYA = [
        SQLConst::LOG_GATYA => [
            "name" => "ノーマルガチャ",
            "image" => "https://pbs.twimg.com/media/FgXkQtHakAA4LFT?format=jpg&name=medium",
            "sub_image" => "https://pbs.twimg.com/media/FgXkRVSagAAU_xm?format=jpg&name=medium",
            "details" => "norma-gacha",
            "ticket" => SQLConst::TICKETS_NORMAL
        ],
        SQLConst::LOG_GATYA_CHRISTMAS_2021 => [
            "name" => "クリスマスガチャ2021", "image" => "textures/gatya_image", "sub_image" => "textures/gatya_image_2", "details" => "reefserver-christmas2021",
            "ticket" => SQLConst::TICKETS_CHRISTMAS_2021
        ],
        SQLConst::LOG_GATYA_SUMMER_2022 => [
            "name" => "サマーガチャ2022", "image" => "", "sub_image" => "", "details" => "summer-event-only-gacha-2022",
            "ticket" => SQLConst::TICKETS_SUMMER_2022
        ],
        SQLConst::LOG_GATYA_HALLOWEEN_NIGHT => ["name" => TextFormat::GOLD . "Halloween" . TextFormat::DARK_PURPLE . "Night" . TextFormat::RESET . "ガチャ",
            "image" => "", "sub_image" => "", "details" => "halloween-pickup-gacha-2022", "ticket" => SQLConst::TICKETS_NORMAL],
        SQLConst::LOG_GATYA_HALLOWEEN_PARTY => ["name" => TextFormat::GOLD . "Halloween" . TextFormat::DARK_GREEN . "Party" . TextFormat::RESET . "ガチャ",
            "image" => "", "sub_image" => "", "details" => "halloween-pickup-gacha-2022", "ticket" => SQLConst::TICKETS_NORMAL],

    ];
}