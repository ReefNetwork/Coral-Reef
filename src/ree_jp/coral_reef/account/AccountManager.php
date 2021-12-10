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
use pocketmine\block\BlockIds;
use pocketmine\level\Position;
use pocketmine\math\Vector3;
use pocketmine\Player;
use pocketmine\scheduler\ClosureTask;
use pocketmine\Server;
use ree_jp\coral_reef\CoralReefPlugin;
use ree_jp\coral_reef\money\MoneyService;
use ree_jp\coral_reef\quest\QuestManager;
use ree_jp\coral_reef\session\SessionData;
use ree_jp\coral_reef\skill\BreakSkill;
use ree_jp\coral_reef\skill\SkillManager;
use ree_jp\coral_reef\sql\SettingConst;
use ree_jp\coral_reef\sql\SQLManager;
use ree_jp\coral_reef\task\ServerUpdateTask;

class AccountManager
{

    const STOP_FLY_WORLD = array('lobby');

    static array $values = array();
    static array $xuid = array();

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

    static function setUp(): void
    {
        SQLManager::$manager->getAllUser(function (array $rows): void {
            $list = [];
            foreach ($rows as $row) {
                $list[$row["xuid"]] = $row["name"];
            }
            self::$xuid = $list;
        });
    }

    static function userJoin(Player $p): void
    {
        $xuid = $p->getXuid();

        SQLManager::$manager->setUser($xuid, $p->getName(), $p->getAddress());
        QuestManager::updateQuests($xuid);
        GiftManager::checkAllExpired($xuid);
        SettingManager::updateNickName($p);
        SettingManager::updateShowCoordinates($p);
        SettingManager::updateOption($p, SettingConst::SNEAK_SKILL);
        SettingManager::updateOption($p, SettingConst::HIDE_SERVER_TIP);
        SettingManager::updateOption($p, SettingConst::NO_FREEZE_WATER);
        SettingManager::updateOption($p, SettingConst::BREAK_UNDER_GROUND);
        SettingManager::updateOption($p, SettingConst::ALLOW_COOL_TIME_DIG);
    }

    static function userQuit(Player $p): void
    {
        $xuid = $p->getXuid();

        if (AccountManager::hasValue($xuid, 'transfer_server')) {
            AccountManager::setValue($xuid, 'transfer_server', 0);
        } else {
            try {
                $account = SQLManager::$manager->getUser($xuid);
                if (is_null($account)) throw new Exception('ユーザーデータがありません');
                $account->save();
            } catch (Exception $e) {
                Server::getInstance()->getLogger()->error($p->getName() . 'のセーブができませんでした' . $e->getMessage());
            }
        }
        if (AccountManager::hasValue($p->getXuid(), 'fly')) { // フライを無効にする
            AccountManager::setValue($p->getXuid(), 'fly', 0);
            $p->setFlying(false);
            $p->setAllowFlight(false);
        }
    }

    /**
     * @param Player $p
     * @param Block $bl
     * @param SessionData $session
     * @throws Exception
     */
    static function blockBroken(Player $p, Block $bl, SessionData $session): void
    {
        $xuid = $p->getXuid();
        $user = SQLManager::$manager->getUser($xuid);
        $skill = $user->skill;
        $user->addXp(ServerUpdateTask::$exp_buff);
        if (!SettingManager::isEnableOption($p->getXuid(), SettingConst::NO_FREEZE_WATER)) { // 水を掘ったら水が消えるように
            if ($bl->getId() === BlockIds::WATER) {
                $p->getLevelNonNull()->setBlock($bl, Block::get(BlockIds::AIR));
            }
        }
        if (self::hasValue($xuid, 'skill_active')) {
            MoneyService::addMoney($xuid, 1);
            $session->breakBlock();
        } else {
            MoneyService::addMoney($xuid, 10);
            $session->breakBlock();
            $session->runSkill();

            if ($skill instanceof BreakSkill && $p->isSurvival()) {
                if (!self::hasValue($xuid, 'skill_cool_time') && !self::hasValue($xuid, 'skill_active') &&
                    !($p->isSneaking() && !SettingManager::isEnableOption($xuid, SettingConst::SNEAK_SKILL))) {
                    if (((($p->getX() - 1) === $bl->getX()) || (($p->getX() + 1) === $bl->getX())) && ((($p->getZ() - 1) === $bl->getZ()) ||
                            (($p->getZ() + 1) === $bl->getZ()))
                        && ($p->getY() - 1 === $bl->getY()) && SettingManager::isEnableOption($xuid, SettingConst::BREAK_UNDER_GROUND)) {
                        $p->sendPopup("地面にスキルをは発動できません\n設定で変更できます");
                        return;
                    }
                    AccountManager::setValue($xuid, 'skill_active');
                    SkillManager::skillActive($p, $bl);
                    AccountManager::setValue($xuid, 'skill_active', 0);
                }
            }
        }
    }

    static function getUserName(string $xuid): string
    {
        $name = "";
        $user = SQLManager::$manager->getUser($xuid);
        if (is_null($user)) {
            if (isset(self::$xuid[$xuid])) {
                $name = self::$xuid[$xuid];
            }
        } else {
            $name = $user->name;
        }
        return $name;
    }

    static function getPlayerByXuid(string $xuid): ?Player
    {
        foreach (Server::getInstance()->getOnlinePlayers() as $p) {
            if ($p->getXuid() === $xuid) return $p;
        }
        return null;
    }

    static function teleport(Player $p, string $levelName, Vector3 $pos = null): void
    {
        $level = Server::getInstance()->getLevelByName($levelName);
        if (is_null($level)) {
            $p->sendMessage('ワールドが見つかりませんでした');
        } else {
            if (is_null($pos)) {
                $pos = $level->getSafeSpawn();
            } else {
                $pos = Position::fromObject($pos, $level);
            }
            $p->teleport($pos);
        }
    }
}
