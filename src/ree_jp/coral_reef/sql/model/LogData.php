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

use ree_jp\coral_reef\account\AccountStore;
use ree_jp\coral_reef\account\KVConst;
use ree_jp\coral_reef\sql\SQLConst;
use ree_jp\coral_reef\StoreHouse;

class LogData
{
    public function __construct(public string $xuid, public string $type, public ?string $subtype, public string $value, public int $time, public ?int $small = null)
    {
    }

    public function toDate(): string
    {
        $date = date(SQLConst::DATE_FORMAT, $this->time);
        if ($this->small != null) {
            $date .= "." . str_pad($this->small, 3, 0, STR_PAD_LEFT);
        }
        return $date;
    }

    static function create(string $xuid, string $type, ?string $subtype, string $value): LogData
    {
        /** @var AccountStore $store */
        $store = StoreHouse::$instance->get(AccountStore::class);
        $time = time();

        $small = $store->getValue($xuid, KVConst::LOG_SMALL . $time);
        if ($small !== null) {
            $small++;
            $store->setValue($xuid, KVConst::LOG_SMALL . $time, 20, $small);
        } else {
            $store->setValue($xuid, KVConst::LOG_SMALL . $time, 20, 0);
        }
        return new LogData($xuid, $type, $subtype, $value, $time, $small);
    }
}
