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

namespace ree_jp\coral_reef\form;

use bbo51dog\bboform\element\Button;
use bbo51dog\bboform\form\SimpleForm;
use pocketmine\Player;
use ree_jp\coral_reef\sql\SQLManager;

class RankingForm
{
    static function sendForm(Player $p): void
    {
        SQLManager::$manager->getAllUser(function (array $rows) use ($p): void {
            $list[] = [];
            foreach ($rows as $row) {
                $list[$row["experience"]][] = $row["name"] . "さん(" . $row["experience"] . ")";
            }
            rsort($list, SORT_NUMERIC);
            $string = "";
            $ranking = 1;
            foreach ($list as $exp => $users) {
                $equal = 0;
                foreach ($users as $user) {
                    $equal++;
                    $string = $string . $ranking . "位: " . $user . "\n";
                }
                $ranking += $equal;
            }
            $p->sendForm((new SimpleForm())->setTitle("ランキング")->setText($string)->addElement(new Button("閉じる")));
        });
    }
}
