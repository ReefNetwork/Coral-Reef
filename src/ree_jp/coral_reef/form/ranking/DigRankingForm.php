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

namespace ree_jp\coral_reef\form\ranking;

use pocketmine\player\Player;
use ree_jp\coral_reef\account\AccountStore;
use ree_jp\coral_reef\form\PageViewForm;
use ree_jp\coral_reef\sql\mysql\SQLRepository;

class DigRankingForm
{

    static function sendDailyDigForm(SQLRepository $repo, AccountStore $store, Player $p): void
    {
        $lastTime = self::getLatestTime();
        $repo->getAllCountWithQuit(strtotime("-1 day", $lastTime), $lastTime, function (array $rows) use ($store, $p): void {
            self::sendCountForm($store, $p, $rows, "Ranking -> DailyDig");
        }, null);
    }

    static private function getLatestTime(): int
    {
        // 5時でリセットする
        if (date("H") < 5) { //5時を下回っていたら昨日の5時
            return strtotime("yesterday 5hour");
        } else { // 5時より上だったらその日の5時
            return strtotime("today 5hour");
        }
    }

    static private function sendCountForm(AccountStore $store, Player $p, array $rows, string $title): void
    {
        $content = [];
        $ranking = 1;
        $my = 0;
        foreach ($rows as $row) {
            $xuid = $row["xuid"];
            $name = $store->getUserName($xuid);

            if ($xuid === $p->getXuid()) {
                $my = $ranking;
            }
            $content[] = "$ranking 位: $name さん(" . $row["break_count"] . ")";
            $ranking++;
        }
        PageViewForm::sendForm($p, $title, "あなたは$my 位です\n\n", $content, 100);
    }

    static function sendWeeklyDigForm(SQLRepository $repo, AccountStore $store, Player $p): void
    {
        $lastTime = self::getLatestTime();
        $repo->getAllCountWithQuit(strtotime("-1 week", $lastTime), $lastTime, function (array $rows) use ($store, $p): void {
            self::sendCountForm($store, $p, $rows, "Ranking -> WeeklyDig");
        }, null);
    }
}