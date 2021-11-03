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

    public string $xuid;
    protected string $value;

    function __construct(string $xuid, string $value)
    {
        $this->xuid = $xuid;
        $this->value = $value;
    }

    function onEvent(string $type, $value): void
    {
    }

    function getProgress(): string
    {
        return "完了";
    }

    abstract function getRewardDetails(): string;

    function outputData(): string
    {
        return $this->value;
    }

    function isExpired(): bool
    {
        return false;
    }

    abstract function isComplete(): bool;
}
