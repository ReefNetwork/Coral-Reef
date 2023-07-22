<?php
/*
 *  CCCCC                        lll RRRRRR                 fff
 * CC    C  oooo  rr rr    aa aa lll RR   RR   eee    eee  ff
 * CC      oo  oo rrr  r  aa aaa lll RRRRRR  ee   e ee   e ffff
 * CC    C oo  oo rr     aa  aaa lll RR  RR  eeeee  eeeee  ff
 *  CCCCC   oooo  rr      aaa aa lll RR   RR  eeeee  eeeee ff
 *
 * Copyright (c) 2023. Ree-jp(https://ree-jp.net)
 */

namespace ree_jp\coral_reef\sql\model;

class LogData
{
    public function __construct(public string $xuid, public string $type, public ?string $subtype, public string $value, public int $time)
    {
    }

    static function create(string $xuid, string $type, ?string $subtype, string $value): LogData
    {
        return new LogData($xuid, $type, $subtype, $value, time());
    }
}
