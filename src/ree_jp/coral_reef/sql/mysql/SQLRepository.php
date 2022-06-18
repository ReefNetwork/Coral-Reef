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
use pocketmine\Server;
use ree_jp\coral_reef\CoralReefPlugin;
use ree_jp\coral_reef\land\LandData;
use ree_jp\coral_reef\money\MoneyCache;
use ree_jp\coral_reef\session\SessionData;
use ree_jp\coral_reef\sql\repo\Repository;
use ree_jp\coral_reef\sql\RepositoryPool;
use ree_jp\coral_reef\sql\SQLConst;

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

    public function getAllUser(Closure $func): void
    {
        $this->pool->getConnection()->executeSelect("coral_reef.user.all", [], $func);
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

    public function addProtectLand(LandData $land, Closure $func, Closure $failure): void
    {
        $this->pool->getConnection()->executeInsert('coral_reef.land.create', ['xuid' => intval($land->xuid), 'name' => $land->name, 'server' => CoralReefPlugin::$serverID,
            'level' => $land->level, 'mx' => $land->aabb->maxX, 'sx' => $land->aabb->minX, 'mz' => $land->aabb->maxZ, 'sz' => $land->aabb->minZ],
            $func, $failure);
    }

    public function deleteProtectLand(LandData $land, Closure $func, Closure $failure): void
    {
        $this->pool->getConnection()->executeGeneric('coral_reef.land.delete', ['xuid' => intval($land->xuid), 'name' => $land->name, 'server' => CoralReefPlugin::$serverID],
            $func, $failure);
    }

    public function addLog(string $xuid, string $type, ?string $subType, ?string $value, ?string $time, ?Closure $func, ?Closure $failure): void
    {
        if ($time === 'now') $time = date(SQLConst::DATE_FORMAT);
        $this->pool->getConnection()->executeInsert('coral_reef.log.add', ['xuid' => intval($xuid), 'type' => $type, 'subtype' => $subType, 'value' => $value,
            'time' => $time], $func, $failure);
    }

    public function getLog(string $xuid, string $type, Closure $func, Closure $failure): void
    {
        $this->pool->getConnection()->executeSelect("coral_reef.log.get.type_sort_newest", ["xuid" => intval($xuid), "type" => $type], $func, $failure);
    }

    public function recordSession(string $xuid, SessionData $session): void
    {
        $this->pool->getConnection()->executeGeneric("coral_reef.session.add", ["xuid" => $xuid, "server" => CoralReefPlugin::$serverID,
            "join_time" => date(SQLConst::DATE_FORMAT, $session->joinTime), "quit_time" => date(SQLConst::DATE_FORMAT, $session->quitTime),
            "break_count" => $session->breakCount, "place_count" => $session->placeCount, "skill_count" => $session->skillCount]);
    }

    public function getRecentSession(string $xuid, Closure $func, ?Closure $failure): void
    {
        $this->pool->getConnection()->executeSelect("coral_reef.session.get_recent", ["xuid" => $xuid, "server" => CoralReefPlugin::$serverID], $func, $failure);
    }

    public function getAllCountWithQuit(int $firstTime, int $lastTime, Closure $func, ?Closure $failure): void
    {
        $this->pool->getConnection()->executeSelect("coral_reef.session.all_get_count_quit_between_sort_desc", ["first_time" => date(SQLConst::DATE_FORMAT, $firstTime),
            "last_time" => date(SQLConst::DATE_FORMAT, $lastTime)], $func, $failure);
    }

    private function noticeByXUid(string $xuid, string $notice): Closure
    {
        return function () use ($notice, $xuid) {
            foreach (Server::getInstance()->getOnlinePlayers() as $p) {
                if ($p->getXuid() === $xuid) $p->sendMessage($notice);
            }
        };
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
        $this->pool->getConnection()->executeGeneric('coral_reef.init.tables.log');
        $this->pool->getConnection()->executeGeneric("coral_reef.init.tables.session");
    }
}
