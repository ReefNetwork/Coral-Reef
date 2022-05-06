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

use ree_jp\coral_reef\sql\mysql\SQLRepository;

abstract class DailyQuest extends QuestData
{
    public int $limit;

    function __construct(SQLRepository $repo, string $xuid, ?string $value, ?int $limit = null)
    {
        if (is_null($limit)) $limit = $this->getDeadTime();
        $array = json_decode($value, true);

        if (!is_null($array) && $array["limit"] === $limit) {
            $this->limit = $array["limit"];
            parent::__construct($repo, $xuid, $array["value"]);
        } else {
            $this->limit = $limit;
            parent::__construct($repo, $xuid, "");
        }
    }

    protected function getDeadTime(): int
    {
        // 5時でリセットする
        if (date("H") < 5) { //5時を下回っていたらその日の5時
            return strtotime("today 5hour");
        } else { // 5時より上だったら次の日の5時
            return strtotime("tomorrow 5hour");
        }
    }

    function outputData(): string
    {
        return json_encode(["limit" => $this->limit, "value" => $this->value]);
    }
}
