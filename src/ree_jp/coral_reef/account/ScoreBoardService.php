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

use pocketmine\network\mcpe\protocol\RemoveObjectivePacket;
use pocketmine\network\mcpe\protocol\SetDisplayObjectivePacket;
use pocketmine\network\mcpe\protocol\SetScorePacket;
use pocketmine\network\mcpe\protocol\types\ScorePacketEntry;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use ree_jp\coral_reef\CoralReefPlugin;
use ree_jp\coral_reef\task\ServerUpdateTask;

class ScoreBoardService
{
    const board = "board";
    const object = "sidebar";

    static function sendScoreBoard(AccountStore $store, Player $p): void
    {
        $user = $store->getUser($p->getXuid());
        if (is_null($user)) return;

        $pk = new RemoveObjectivePacket();
        $pk->objectiveName = self::board;
        $p->getNetworkSession()->sendDataPacket($pk);
        $pk = new SetDisplayObjectivePacket();
        $pk->displaySlot = self::object;
        $pk->objectiveName = self::board;
        $pk->displayName = TextFormat::GREEN . "Reef " . TextFormat::YELLOW . "Server " . TextFormat::DARK_GRAY . CoralReefPlugin::$serverID;
        $pk->criteriaName = "dummy";
        $pk->sortOrder = 0;
        $p->getNetworkSession()->sendDataPacket($pk);

        $pk = new SetScorePacket();
        $pk->type = SetScorePacket::TYPE_CHANGE;

        $skillName = is_null($user->skill) ? "なし" : $user->skill->name;
        self::setScore($pk, 1, "現在のスキル : " . $skillName);

        $nextExp = $user->necessaryExperience;
        if ($user->level === array_key_last(Experiment::LEVEL_EXPERIMENT)) $nextExp = "レベル上限";
        self::setScore($pk, 2, "次のレベルまで : " . $nextExp);

        if (ServerUpdateTask::$exp_buff > 1) {
            self::setScore($pk, 3, "経験値ボーナス : " . ServerUpdateTask::$exp_buff . "倍");
        }

        if (ServerUpdateTask::$haste_effect >= 0) {
            self::setScore($pk, 4, "採掘速度アップ : " . (ServerUpdateTask::$haste_effect + 2) . "倍");
        }

        self::setScore($pk, 8, TextFormat::DARK_GRAY . $p->getDisplayName());
        self::setScore($pk, 9, TextFormat::DARK_GRAY . date("Y/m/d H:i:s"));

        if ($store->hasValue($p->getXuid(), "wait_action")) {
            self::setScore($pk, 11, "現在処理中です....");
            self::setScore($pk, 11, "数秒たってもこの状態の場合は");
            self::setScore($pk, 13, "/lobbyでリログをお願いします");
        }
        if (CoralReefPlugin::$plugin->isDev) {
            self::setScore($pk, 15, CoralReefPlugin::$plugin->getDescription()->getVersion());
        }
        $p->getNetworkSession()->sendDataPacket($pk);
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
