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

namespace ree_jp\coral_reef\account;


use Exception;
use pocketmine\block\Block;
use pocketmine\item\Item;
use pocketmine\level\Position;
use pocketmine\Player;
use pocketmine\scheduler\ClosureTask;
use pocketmine\Server;
use ree_jp\coral_reef\CoralReefPlugin;
use ree_jp\coral_reef\skill\BreakSkill;
use ree_jp\coral_reef\skill\SkillManager;
use ree_jp\coral_reef\sql\SQLManager;

class AccountManager
{

    const STOP_FLY_WORLD = array('lobby');

    static array $values = array();

    static function setValue(string $xuid, string $value, int $tick = null): void
    {
        $key = $xuid . ':' . $value;
        if ($tick === 0) {
            if (self::hasValue($xuid, $value)) {
                unset(self::$values[$key]);
            }
        } else {
            self::$values[$key] = $tick;
            if (is_int($tick)) {
                CoralReefPlugin::$plugin->getScheduler()->scheduleDelayedTask(
                    new ClosureTask(function () use ($key): void {
                        if (array_key_exists($key, self::$values)) {
                            unset(self::$values[$key]);
                        }
                    }), $tick);
            }
        }
    }

    static function hasValue(string $xuid, string $value): bool
    {
        $key = $xuid . ':' . $value;
        return array_key_exists($key, self::$values);
    }

    static function userJoin(Player $p): void
    {
        $xuid = $p->getXuid();

        SQLManager::$manager->setUser($xuid, $p->getName(), $p->getAddress());
        SQLManager::$manager->addLog($xuid, 'join', 'now', null, $p->getAddress());
        SettingManager::updateNickName($p);
        SettingManager::updateShowCoordinates($p);
        SettingManager::updateSneakSkill($p);
    }

    static function userQuit(Player $p, string $reason): void
    {
        $xuid = $p->getXuid();

        try {
            $account = SQLManager::$manager->getUser($xuid);
            if (is_null($account)) throw new Exception('ユーザーデータがありません');
            $account->save();
        } catch (Exception $e) {
            Server::getInstance()->getLogger()->error($p->getName() . 'のセーブができませんでした' . $e->getMessage());
        }
        try {
            SQLManager::$manager->addLog($xuid, 'quit', 'now', null, $reason);
        } catch (Exception $e) {
            Server::getInstance()->getLogger()->error("[Log] {$p->getName()} の処理中に " . $e->getMessage());
        }
        self::setValue($xuid, 'rejoin', 60 * 3 * 20);
    }

    /**
     * @param Player $p
     * @param Block $bl
     * @param Item $item
     * @throws Exception
     */
    static function brockBroken(Player $p, Block $bl, Item $item): void
    {
        $xuid = $p->getXuid();
        $user = SQLManager::$manager->getUser($xuid);
        $skill = $user->skill;
        $logDetail = $bl->x . ':' . $bl->y . ':' . $bl->z . '/' . $item->getVanillaName() . '/' . $bl->getName();
        SQLManager::$manager->addLog($xuid, 'break', 'now', null, $logDetail);
        $user->addXp();
        if ($skill instanceof BreakSkill && $p->isSurvival()) {
            if (!self::hasValue($xuid, 'skill_cool_time') && !self::hasValue($xuid, 'skill_active') &&
                !($p->isSneaking() && SettingManager::isSneakSkill($xuid))) {
                AccountManager::setValue($xuid, 'skill_active');
                $logDetail = $bl->x . ':' . $bl->y . ':' . $bl->z . '/' . $skill->id;
                SQLManager::$manager->addLog($xuid, 'skill', 'now', null, $logDetail);
                SkillManager::skillActive($p, $bl);
                AccountManager::setValue($xuid, 'skill_active', 0);
            }
        }
    }

    static function getUserName(string $xuid): string
    {
        $name = "";
        try {
            $user = SQLManager::$manager->getUser($xuid);
            if (!is_null($user)) $name = $user->name;
        } catch (Exception $e) {
            Server::getInstance()->getLogger()->critical("[GETUserName]" . $e->getMessage());
        }
        return $name;
    }

    static function teleport(Player $p, string $levelName, Position $pos = null): void
    {
        $level = Server::getInstance()->getLevelByName($levelName);
        if (is_null($level)) {
            $p->sendMessage('ワールドが見つかりませんでした');
        } else {
            if (is_null($pos)) {
                $pos = $level->getSpawnLocation();
            } else {
                $pos = $pos->setLevel($level);
            }
            $p->teleport($pos);
        }
    }
}
