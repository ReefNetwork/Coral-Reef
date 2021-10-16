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

class LevelUpQuest extends QuestData
{
    const ID = "level_up";
    const NAME = "レベルアップ";
    const SHORT_DETAILS = "レベルアップしよう!";
    const EXPLANATION = "ブロックを掘ると経験値を入手できます。経験値を一定量集めてサーバーのレベルを上げましょう。";

    function getRewardDetails(): string
    {
        // TODO: Implement getRewardDetails() method.
    }

    function outputData(): string
    {
        // TODO: Implement outputData() method.
    }

    function isExpired(): bool
    {
        // TODO: Implement isExpired() method.
    }

    function isComplete(): bool
    {
        // TODO: Implement isComplete() method.
    }
}
