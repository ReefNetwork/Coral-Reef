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
    const SKILLS = ['first', 'second', 'third', 'fourth', 'fifth', 'sixth', 'test'];

    static array $reduceCoolTime = [];

    static function getSkill(?string $skill): ?BreakSkill
    { // https://en.wikipedia.org/wiki/List_of_reefs
        switch ($skill) {
            case 'first': // https://en.wikipedia.org/wiki/Angria_Bank
                return new BreakSkill('アングリア', $skill, 0, 1, 0, 0, 1);

            case 'second': // https://en.wikipedia.org/wiki/Apo_Reef
                return new BreakSkill('アポ', $skill, 0, 1, 0, 1, 3);

            case 'third': // https://en.wikipedia.org/wiki/Arrecifes_de_Cozumel_National_Park
                return new BreakSkill('パランカー', $skill, 0, 1, 2, 0, 5);

            case 'fourth': // https://en.wikipedia.org/wiki/Bar_Reef
                return new BreakSkill('パー', $skill, 0, 2, 2, 0, 8);

            case 'fifth': // https://en.wikipedia.org/wiki/Belize_Barrier_Reef
                return new BreakSkill('ベリーズバリア', $skill, 10, 2, 2, 1, 10);

            case 'sixth': // https://en.wikipedia.org/wiki/Benares_Shoals
                return new BreakSkill('ベナレスショールス', $skill, 15, 2, 2, 2, 13);

            default:
                return null;
        }
    }

    static function reduceCoolTime(string $xuid, int $tick): void // マイナス入れるとクールタイムが増える
    {
        if ($tick === 0 && isset(self::$reduceCoolTime[$xuid])) {
            unset(self::$reduceCoolTime[$xuid]);
        } elseif ($tick !== 0) self::$reduceCoolTime[$xuid] = $tick;
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
        $cool_time = $skill->cool_time;
        if (isset(self::$reduceCoolTime[$xuid])) { // クールタイム減らすのを反映
            $cool_time -= self::$reduceCoolTime[$xuid];
        }
        if ($cool_time > 0) { // クールタイムが0以上のときクールタイムの処理をする
            AccountManager::setValue($xuid, 'skill_cool_time');
            CoralReefPlugin::$plugin->getScheduler()->scheduleDelayedTask(new ClosureTask(function (int $currentTick) use ($xuid, $p): void {
                $p->sendPopup('スキルのクールタイムが終了しました');
                AccountManager::setValue($xuid, 'skill_cool_time', 0);
                $volume = 0x10000000 * (min(30, $currentTick) / 5);
                $p->getLevel()->broadcastLevelSoundEvent($p, LevelSoundEventPacket::SOUND_LEVELUP, (int)$volume);
            }), $cool_time);
        }
    }
}
