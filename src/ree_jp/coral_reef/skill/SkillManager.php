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

namespace ree_jp\coral_reef\skill;

use Exception;
use pocketmine\block\Block;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\Player;
use pocketmine\scheduler\ClosureTask;
use ree_jp\coral_reef\account\AccountManager;
use ree_jp\coral_reef\CoralReefPlugin;
use ree_jp\coral_reef\sql\SQLManager;

class SkillManager
{
    const SKILLS = ['double', 'triple', 'test'];

    static function getSkill(?string $skill): ?BreakSkill
    {
        switch ($skill) {
            case 'double':
                return new BreakSkill('ダブル', $skill, 0, 1, 0, 0);

            case 'triple':
                return new BreakSkill('トリプル', $skill, 0, 2, 2, 2);

            case 'test':
                return new BreakSkill('てすと', $skill, 10, 9, 2, 2);

            default:
                return null;
        }
    }

    /**
     * @throws Exception
     */
    static function skillActive(Player $p, Block $bl): void
    {
        $xuid = $p->getXuid();
        $user = SQLManager::$manager->getUser($xuid);
        $skill = $user->skill;
        if (is_null($skill)) throw new Exception('スキルが設定されていません');

        $skill->runSkill($bl, $p);
        if ($skill->cool_time !== 0) {
            AccountManager::setValue($xuid, 'skill_cool_time', $skill->cool_time);
            CoralReefPlugin::$plugin->getScheduler()->scheduleDelayedTask(new ClosureTask(function (int $currentTick) use ($p): void {
                $p->sendTip('スキルのクールタイムが終了しました');
                AccountManager::setValue($p->getXuid(), 'tip_cool_time', 60);
                $volume = 0x10000000 * (min(30, $currentTick) / 5);
                $p->getLevel()->broadcastLevelSoundEvent($p, LevelSoundEventPacket::SOUND_LEVELUP, (int)$volume);
            }), $skill->cool_time);
        }
    }
}
