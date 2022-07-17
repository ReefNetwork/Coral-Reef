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

use Generator;
use pocketmine\player\Player;
use ree_jp\coral_reef\account\AccountStore;
use ree_jp\coral_reef\form\PageViewForm;
use ree_jp\coral_reef\sql\model\BlockStatisticsModel;
use ree_jp\coral_reef\sql\repo\SessionRepository;
use ree_jp\coral_reef\sql\RepositoryPool;
use SOFe\AwaitGenerator\Await;

class DigRankingForm
{

    static function sendDailyDigForm(RepositoryPool $pool, AccountStore $store, Player $p): void
    {
        Await::f2c(function () use ($pool, $p, $store): Generator {
            $lastTime = self::getLatestTime();
            /** @var SessionRepository */
            $repo = $pool->get(SessionRepository::class);

            /** @var BlockStatisticsModel[] */
            $statistics = yield from $repo->getAllCountWithJoin(strtotime("-1 day", $lastTime), $lastTime);

            self::sendCountForm($store, $p, $statistics, "Ranking -> DailyDig");
        });
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

    /**
     * @param AccountStore $store
     * @param Player $p
     * @param BlockStatisticsModel[] $statistics
     * @param string $title
     * @return void
     */
    static private function sendCountForm(AccountStore $store, Player $p, array $statistics, string $title): void
    {
        $content = [];
        $ranking = 1;
        $my = 0;

        foreach ($statistics as $row) {
            $name = $store->getUserName($row->xuid);

            if ($row->xuid === $p->getXuid()) {
                $my = $ranking;
            }
            $content[] = "$ranking 位: $name さん(" . $row->breakCount . ")";
            $ranking++;
        }
        PageViewForm::sendForm($p, $title, "あなたは$my 位です\n\n", $content, 100);
    }

    static function sendWeeklyDigForm(RepositoryPool $pool, AccountStore $store, Player $p): void
    {
        Await::f2c(function () use ($pool, $p, $store): Generator {
            $lastTime = self::getLatestTime();
            /** @var SessionRepository */
            $repo = $pool->get(SessionRepository::class);

            /** @var BlockStatisticsModel[] */
            $statistics = yield from $repo->getAllCountWithJoin(strtotime("-1 week", $lastTime), $lastTime);

            self::sendCountForm($store, $p, $statistics, "Ranking -> WeeklyDig");
        });
    }
}