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

namespace ree_jp\coral_reef;

use RuntimeException;

class StoreHouse
{
    /** @var Store[] */
    private array $stores = [];

    static StoreHouse $instance;

    /**
     * @param string $identity
     * @return Store
     */
    public function get(string $identity): Store
    {
        if (isset($this->stores[$identity])) return $this->stores[$identity];
        throw new RuntimeException("store 「{$identity}」 not found");
    }

    public function register(Store $store, string $identity): void
    {
        $this->stores[$identity] = $store;
    }
}
