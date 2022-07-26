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

namespace ree_jp\coral_reef\sql;

interface SQLConst
{
    const DATE_FORMAT = "Y-m-d H:i:s";

    const NOW_TIME = "now";
    const NULL = "empty";
    const COMPLETE = "complete";

    const TYPE_ENV = "server_environment";
    const TYPE_SETTINGS = "setting";
    const TYPE_TICKETS = "tickets";
    const TYPE_GIFT = "gift";
    const TYPE_QUEST = "quest";
    const TYPE_BONUS = "bonus";
    const TYPE_LAND_KEY = "land_key";

    const ENV_HASTE_EFFECT = "haste_effect";
    const ENV_EXP_BUF = "experience_buff";

    const TICKETS_NORMAL = "normal_tickets";
    const TICKETS_CHRISTMAS_2021 = "christmas_2021_tickets";
    const TICKETS_SUMMER_2022 = "summer_2022_tickets";

    const LOG_QUEST = "quest";
    const LOG_BONUS = "bonus";
    const LOG_GATYA = "gatya";
    const LOG_GATYA_CHRISTMAS_2021 = "gatya_christmas_2021";
    const LOG_GATYA_SUMMER_2022 = "gatya_summer_2022";
}
