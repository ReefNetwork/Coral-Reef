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

namespace ree_jp\coral_reef\sql\mysql;

use Closure;
use ree_jp\coral_reef\money\MoneyCache;
use ree_jp\coral_reef\sql\repo\Repository;
use ree_jp\coral_reef\sql\RepositoryPool;

class SQLRepository implements Repository
{
    public function __construct(private RepositoryPool $pool, bool $isInit)
    {
        if ($isInit) {
            $this->createFunction();
            $this->createTable();
        }
    }

    public function close(): void
    {
        MoneyCache::purgeAll($this);
    }

    public function getMoney(string $xuid, ?Closure $func, ?Closure $failure = null): void
    {
        $this->pool->getConnection()->executeSelect('coral_reef.money.get', ['xuid' => intval($xuid)], $func, $failure);
    }

    public function addMoney(string $xuid, int $money, ?Closure $func, ?Closure $failure = null): void
    {
        $this->pool->getConnection()->executeInsert('coral_reef.money.add', ["xuid" => intval($xuid), "money" => $money], $func, $failure);
    }

    public function getValue(string $xuid, string $type, string $subtype, ?Closure $func, ?Closure $failure = null): void
    {
        $this->pool->getConnection()->executeSelect('coral_reef.values.get.one', ['xuid' => intval($xuid), 'type' => strtolower($type), 'subtype' => strtolower($subtype)],
            $func, $failure);
    }

    public function getAllSubtypeValue(string $xuid, string $type, ?Closure $func, ?Closure $failure = null): void
    {
        $this->pool->getConnection()->executeSelect('coral_reef.values.get.all_subtype', ['xuid' => $xuid, 'type' => strtolower($type)],
            $func, $failure);
    }

    public function getAllUserSubtypeValue(string $type, ?Closure $func, ?Closure $failure = null): void
    {
        $this->pool->getConnection()->executeSelect("coral_reef.values.get.all_user_subtype", ["type" => strtolower($type)],
            $func, $failure);
    }

    public function setValue(string $xuid, string $type, string $subtype, ?string $value, ?Closure $func, ?Closure $failure = null): void
    {
        $this->pool->getConnection()->executeInsert('coral_reef.values.set',
            ['xuid' => intval($xuid), 'type' => strtolower($type), 'subtype' => strtolower($subtype), 'value' => $value], $func, $failure);
    }

    public function addValue(string $xuid, string $type, string $subtype, int $value, ?Closure $func, ?Closure $failure = null): void
    {
        $this->pool->getConnection()->executeInsert('coral_reef.values.add',
            ['xuid' => intval($xuid), 'type' => strtolower($type), 'subtype' => strtolower($subtype), 'value' => $value], $func, $failure);
    }

    public function deleteValue(string $xuid, string $type, string $subtype, ?Closure $func, ?Closure $failure = null): void
    {
        $this->pool->getConnection()->executeGeneric('coral_reef.values.delete', ['xuid' => intval($xuid), 'type' => strtolower($type), 'subtype' => strtolower($subtype)],
            $func, $failure);
    }

    private function createFunction(): void
    {
        $this->pool->getConnection()->executeGeneric("coral_reef.init.functions.add_value.reset");
        $this->pool->getConnection()->executeGeneric("coral_reef.init.functions.add_value.create");
    }

    private function createTable(): void
    {
        $this->pool->getConnection()->executeGeneric('coral_reef.init.tables.money');
        $this->pool->getConnection()->executeGeneric('coral_reef.init.tables.virtual_value');
    }
}
