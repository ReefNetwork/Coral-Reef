<?php
/*
 *  CCCCC                        lll RRRRRR                 fff
 * CC    C  oooo  rr rr    aa aa lll RR   RR   eee    eee  ff
 * CC      oo  oo rrr  r  aa aaa lll RRRRRR  ee   e ee   e ffff
 * CC    C oo  oo rr     aa  aaa lll RR  RR  eeeee  eeeee  ff
 *  CCCCC   oooo  rr      aaa aa lll RR   RR  eeeee  eeeee ff
 *
 * Copyright (c) 2021-2021. Ree-jp(https://ree-jp.net)
 */

namespace ree_jp\coral_reef\quest\data;

abstract class QuestData
{
    const ID = "unknown";
    const NAME = "unknown";
    const SHORT_DETAILS = "unknown";
    const EXPLANATION = "unknown";

    private string $value;

    function __construct(string $value)
    {
        $this->value = $value;
    }

    abstract function getRewardDetails(): string;

    abstract function outputData(): string;

    abstract function isExpired(): bool;

    abstract function isComplete(): bool;
}
