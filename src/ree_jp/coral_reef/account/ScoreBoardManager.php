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
use pocketmine\network\mcpe\protocol\RemoveObjectivePacket;
use pocketmine\network\mcpe\protocol\SetDisplayObjectivePacket;
use pocketmine\network\mcpe\protocol\SetScorePacket;
use pocketmine\network\mcpe\protocol\types\ScorePacketEntry;
use pocketmine\Player;
use pocketmine\utils\TextFormat;
use ree_jp\coral_reef\CoralReefPlugin;
use ree_jp\coral_reef\sql\SQLManager;
use ree_jp\coral_reef\task\ServerUpdateTask;

class ScoreBoardManager
{
    const board = 'board';
    const object = 'sidebar';

    /**
     * @param Player $p
     * @throws Exception
     */
    static function sendScoreBoard(Player $p): void
    {
        $user = SQLManager::$manager->getUser($p->getXuid());
        if (is_null($user)) return;

        $pk = new RemoveObjectivePacket();
        $pk->objectiveName = self::board;
        $p->sendDataPacket($pk);
        $pk = new SetDisplayObjectivePacket();
        $pk->displaySlot = self::object;
        $pk->objectiveName = self::board;
        $pk->displayName = TextFormat::GREEN . 'Reef ' . TextFormat::YELLOW . 'Server';
        $pk->criteriaName = "dummy";
        $pk->sortOrder = 0;
        $p->sendDataPacket($pk);

        $pk = new SetScorePacket();
        $pk->type = SetScorePacket::TYPE_CHANGE;
        $skillName = is_null($user->skill) ? 'なし' : $user->skill->name;
        self::setScore($pk, 1, '現在のスキル : ' . $skillName);
        self::setScore($pk, 2, '次のレベルまで : ' . $user->necessaryExperience);
        if (ServerUpdateTask::$exp_buff > 1) {
            self::setScore($pk, 3, '経験値ボーナス : ' . ServerUpdateTask::$exp_buff . "倍");
        }
        if (ServerUpdateTask::$haste_effect >= 0) {
            self::setScore($pk, 4, '採掘速度アップ : ' . (ServerUpdateTask::$haste_effect + 1) . "倍");
        }
        self::setScore($pk, 8, TextFormat::DARK_GRAY . $p->getDisplayName());
        self::setScore($pk, 9, TextFormat::DARK_GRAY . date("Y/m/d H:i:s"));

        if (CoralReefPlugin::$plugin->isDev) {
            self::setScore($pk, 13, CoralReefPlugin::$plugin->getDescription()->getVersion());
        }
        $p->sendDataPacket($pk);
    }

    private static function setScore(SetScorePacket $pk, int $score, string $content): void
    {
        $entry = new ScorePacketEntry();
        $entry->objectiveName = self::board;
        $entry->type = 3;
        $entry->customName = $content;
        $entry->score = $score;
        $entry->scoreboardId = $score;
        $pk->entries[$score] = $entry;
    }
}
