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

namespace ree_jp\coral_reef\sql\mysql;

use Generator;
use ree_jp\coral_reef\account\UserAccount;
use ree_jp\coral_reef\sql\repo\UserRepository;
use ree_jp\coral_reef\sql\RepositoryPool;
use SOFe\AwaitGenerator\Await;

class MysqlUserRepo implements UserRepository
{
    public function __construct(private RepositoryPool $pool, bool $isInit)
    {
        if ($isInit) {
            $pool->getConnection()->executeGeneric("coral_reef.init.tables.user");
        }
    }

    public function getUserData(string $xuid): Generator
    {
        $result = yield from Await::promise(
            fn($resolve, $reject) => $this->pool->getConnection()->executeSelect("coral_reef.user.get", ["xuid" => $xuid], $resolve, $reject));
        if (!$result) return null;
        return $this->setUserDataModel(current($result));
    }

    private function setUserDataModel(array $data): ?UserAccount
    {
        if (empty($data)) return null;

        return new UserAccount($data["xuid"], $data["name"], $data["experience"], $data["skill"]);
    }

    public function setUserData(UserAccount $data): Generator
    {
        $skillId = $data->skill?->id;
        yield from Await::promise(
            fn($resolve, $reject) => $this->pool->getConnection()->executeInsert("coral_reef.user.set.account", ["xuid" => $data->xuid,
                "name" => $data->name, "experience" => $data->experience, "skill" => $skillId], $resolve, $reject));
    }

    public function close(): void
    {
    }
}