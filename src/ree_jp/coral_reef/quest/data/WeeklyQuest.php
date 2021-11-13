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

namespace ree_jp\coral_reef\quest\data;

abstract class WeeklyQuest extends DailyQuest
{
    function __construct(string $xuid, ?string $value)
    {
        parent::__construct($xuid, $value, $this->getDeadTime());
    }

    protected function getDeadTime(): int
    {
        // 月曜の5時でリセットする
        if (date("N") === "1" && date("H") < 5) { //5時を下回っていたらその日の5時
            return strtotime('today 5hour');
        } else { // 次の月曜の5時
            return strtotime("next monday 5hour");
        }
    }
}
