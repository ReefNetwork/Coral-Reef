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
    const DATE_FORMAT = 'Y-m-d H:i:s';

    const NOW_TIME = 'now';
    const TYPE_NULL = 'empty';

    const TYPE_ENV = 'server_environment';
    const TYPE_SETTINGS = 'setting';
    const TYPE_TICKETS = 'tickets';
    const TYPE_GIFT = 'gift';
    const TYPE_QUEST = 'quest';

    const ENV_HASTE_EFFECT = "haste_effect";
    const ENV_EXP_BUF = "experience_buff";

    const TICKETS_NORMAL = 'normal_tickets';

    const LOG_GATYA = 'gatya';
}
