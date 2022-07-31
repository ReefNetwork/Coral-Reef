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

namespace ree_jp\coral_reef\quest\data\event\summer_2022;

use ree_jp\coral_reef\quest\data\DailyDigQuest;
use ree_jp\coral_reef\sql\SQLConst;

class Summer2022DailyDig extends DailyDigQuest
{
    const ID = "summer_2022_dig";
    const NAME = "2022夏限定!整地(毎日)";

    const BONUS_TICKET = SQLConst::TICKETS_SUMMER_2022;
    const BONUS_TICKET_NAME = "§bサマー§rガチャチケット";
}