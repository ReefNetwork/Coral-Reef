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

abstract class DailyQuest extends QuestData
{
    protected int $limit;

    function __construct(string $xuid, ?string $value)
    {
        $array = json_decode($value, true);

        if (!is_null($array) && $array["limit"] === $this->getDeadTime()) {
            $this->limit = $array["limit"];
            parent::__construct($xuid, $array["value"]);
        } else {
            $this->limit = $this->getDeadTime();
            parent::__construct($xuid, "");
        }
    }

    private function getDeadTime(): int
    {
        // 5時でリセットする
        if (date("H") < 5) { //5時を下回っていたらその日の5時
            return mktime(5, 0, 0, date('n'), date('j'), date('Y'));
        } else { // 5時より上だったら次の日の5時
            return mktime(5, 0, 0, date('n'), date('j') + 1, date('Y'));
        }
    }

    function outputData(): string
    {
        return json_encode(["limit" => $this->limit, "value" => $this->value]);
    }
}
