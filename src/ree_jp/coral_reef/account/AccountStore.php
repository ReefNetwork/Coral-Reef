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

namespace ree_jp\coral_reef\account;

use pocketmine\scheduler\ClosureTask;
use ree_jp\coral_reef\CoralReefPlugin;
use ree_jp\coral_reef\sql\SQLRepository;

class AccountStore
{
    public array $users = [];
    private array $values = array();
    private array $xuid = array();

    public function setValue(string $xuid, string $value, int $tick = null, $data = null): void
    {
        $key = $xuid . ':' . $value;
        if ($tick === 0) {
            if (self::hasValue($xuid, $value)) {
                unset($this->values[$key]);
            }
        } else {
            $this->values[$key] = $data;
            if (is_int($tick)) {
                CoralReefPlugin::$plugin->getScheduler()->scheduleDelayedTask(
                    new ClosureTask(function () use ($key): void {
                        if (array_key_exists($key, $this->values)) {
                            unset($this->values[$key]);
                        }
                    }), $tick);
            }
        }
    }

    public function hasValue(string $xuid, string $value): bool
    {
        $key = $xuid . ':' . $value;
        return array_key_exists($key, $this->values);
    }

    public function getValue(string $xuid, string $value)
    {
        $key = $xuid . ':' . $value;
        return $this->values[$key] ?? null;
    }

    public function getUserName(string $xuid): string
    {
        $name = "";
        $user = $this->getUser($xuid);
        if (is_null($user)) {
            if (isset($this->xuid[$xuid])) {
                $name = $this->xuid[$xuid];
            }
        } else {
            $name = $user->name;
        }
        return $name;
    }

    public function getXuid(string $name): ?string
    {
        $xuid = array_search($name, $this->xuid, true);
        if ($xuid === false) {
            return null;
        } else return $xuid;
    }

    public function getUser(string $xuid): ?UserAccount // 今サーバーに参加してるプレイヤーのみ取得できる
    {
        if (array_key_exists($xuid, $this->users)) return $this->users[$xuid];
        return null;
    }

    public function updateUserNameList(SQLRepository $repo): void
    {
        $repo->getAllUser(function (array $rows): void {
            $list = [];
            foreach ($rows as $row) {
                $list[$row["xuid"]] = $row["name"];
            }
            $this->xuid = $list;
        });
    }
}
