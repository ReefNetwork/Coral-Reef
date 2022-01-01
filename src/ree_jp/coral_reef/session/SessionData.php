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

namespace ree_jp\coral_reef\session;

use ree_jp\coral_reef\sql\SQLManager;

class SessionData
{
    public int $joinTime;
    public int $quitTime;

    public int $breakCount = 0;
    public int $placeCount = 0;
    public int $skillCount = 0;

    public function __construct(private string $xuid)
    {
        $this->joinTime = time();
    }

    public function breakBlock(): void
    {
        $this->breakCount++;
    }

    public function placeBlock(): void
    {
        $this->placeCount++;
    }

    public function runSkill(): void
    {
        $this->skillCount++;
    }

    public function quit(SQLManager $repo): void
    {
        $this->quitTime = time();
        $repo->recordSession($this->xuid, $this);
    }
}
