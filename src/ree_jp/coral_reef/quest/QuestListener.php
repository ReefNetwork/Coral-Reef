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

namespace ree_jp\coral_reef\quest;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;

class QuestListener implements Listener
{
    /**
     * クエスト用のListener
     * イベントの値は絶対に変更しないように
     */

    /**
     * @priority MONITOR
     */
    function onJoin(PlayerJoinEvent $ev): void
    {
    }
}
