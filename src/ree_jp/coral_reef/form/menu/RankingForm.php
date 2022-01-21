<?php
/*
 *  CCCCC                        lll RRRRRR                 fff
 * CC    C  oooo  rr rr    aa aa lll RR   RR   eee    eee  ff
 * CC      oo  oo rrr  r  aa aaa lll RRRRRR  ee   e ee   e ffff
 * CC    C oo  oo rr     aa  aaa lll RR  RR  eeeee  eeeee  ff
 *  CCCCC   oooo  rr      aaa aa lll RR   RR  eeeee  eeeee ff
 *
 * Copyright (c) 2021-2022. Ree-jp(https://ree-jp.net)
 */

namespace ree_jp\coral_reef\form\menu;

use PageViewForm;
use pocketmine\player\Player;
use ree_jp\coral_reef\sql\SQLRepository;

class RankingForm
{
    static function sendForm(SQLRepository $repo, Player $p): void
    {
        $repo->getAllUser(function (array $rows) use ($p): void {
            if (!$p->isOnline()) return;

            $list[] = [];
            foreach ($rows as $row) {
                if ($row["xuid"] === 0) continue; // サーバー管理用アカウントをランキングに入れない
                $list[$row["experience"]][] = $row["name"];
            }
            krsort($list, SORT_NUMERIC);
            $content = [];
            $ranking = 1;
            $my = 0;
            foreach ($list as $exp => $users) {
                $equal = 0;
                foreach ($users as $user) {
                    if ($user === $p->getName()) {
                        $my = $ranking;
                    }
                    $equal++;
                    $content[] = "$ranking 位: $user さん($exp)" . "\n";
                }
                $ranking += $equal;
            }
            PageViewForm::sendForm($p, "ランキング", "あなたは" . $my . "位です\n\n", $content, 100);
        });
    }
}
