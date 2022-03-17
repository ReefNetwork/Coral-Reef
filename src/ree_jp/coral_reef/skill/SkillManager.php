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
use JetBrains\PhpStorm\Pure;
use pocketmine\block\Block;
use pocketmine\player\Player;
use pocketmine\scheduler\ClosureTask;
use pocketmine\world\sound\XpLevelUpSound;
use ree_jp\coral_reef\account\AccountStore;
use ree_jp\coral_reef\CoralReefPlugin;
use ree_jp\coral_reef\quest\QuestListener;

class SkillManager
{
    const SKILLS = ['first', 'second', 'third', 'fourth', 'fifth', 'sixth', "seventh", "eighth", "ninth", "tenth", "eleventh", "twelfth", "thirteenth",
        "fourteenth", "fifteenth", "sixteenth", "seventeenth", "eighteenth", "nineteenth", "twentieth", "twenty-first", "twenty-second", "twenty-third"];

    static array $reduceCoolTime = [];

    #[Pure] static function getSkill(?string $skill): ?BreakSkill
    {
        return match ($skill) { // https://en.wikipedia.org/wiki/List_of_reefs
            'first' => new BreakSkill('アングリア', $skill, 0, 1, 0, 0, 1, "1×2"),
            'second' => new BreakSkill('アポ', $skill, 0, 1, 0, 1, 3, "1×2×2"),
            'third' => new BreakSkill('パランカー', $skill, 0, 1, 2, 0, 5, "3×2"),
            'fourth' => new BreakSkill('パー', $skill, 0, 2, 2, 0, 8, "3×3"),
            'fifth' => new BreakSkill('ベリーズバリア', $skill, 10, 2, 2, 1, 10, "3×3×2"),
            'sixth' => new BreakSkill('ベナレスショールス', $skill, 15, 2, 2, 2, 13, "3×3×3"),
            "seventh" => new BreakSkill("トライアングル", $skill, 10, 4, 4, 0, 15, "5×5"),
            "eighth" => new BreakSkill("デインツリー", $skill, 15, 4, 4, 1, 18, "5×5×2"),
            "ninth" => new BreakSkill("ダーウィン", $skill, 20, 4, 4, 2, 20, "5×5×3"),
            "tenth" => new BreakSkill("フィリッポ", $skill, 25, 4, 4, 3, 23, "5×5×4"),
            "eleventh" => new BreakSkill("フリンダーズ", $skill, 30, 4, 4, 4, 25, "5×5×5"),
            "twelfth" => new BreakSkill("フレンチフリゲート", $skill, 13, 6, 6, 0, 28, "7×7×1"),
            "thirteenth" => new BreakSkill("グレートバリア", $skill, 30, 6, 6, 2, 30, "7×7×3"),
            "fourteenth" => new BreakSkill("ベレス", $skill, 45, 6, 6, 4, 33, "7×7×5"),
            "fifteenth" => new BreakSkill("キングマン", $skill, 60, 6, 6, 6, 35, "7×7×7"),
            "sixteenth" => new BreakSkill("ランズダウン", $skill, 40, 8, 8, 2, 38, "9×9×3"),
            "seventeenth" => new BreakSkill("リラ", $skill, 65, 8, 8, 4, 40, "9×9×5"),
            "eighteenth" => new BreakSkill("マヌエルルイス", $skill, 90, 8, 8, 6, 43, "9×9×7"),
            "nineteenth" => new BreakSkill("マロ", $skill, 115, 8, 8, 8, 45, "9×9×9"),
            "twentieth" => new BreakSkill("メソバリア", $skill, 95, 10, 10, 4, 48, "11×11×5"),
            "twenty-first" => new BreakSkill("ロックス", $skill, 125, 10, 10, 6, 50, "11×11×7"),
            "twenty-second" => new BreakSkill("マイアミテラス", $skill, 155, 10, 10, 8, 53, "11×11×9"),
            "twenty-third" => new BreakSkill("マーカス", $skill, 185, 10, 10, 10, 55, "11×11×11"),
            default => null,
        };
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
    static function skillActive(AccountStore $store, Player $p, Block $bl): void
    {
        $xuid = $p->getXuid();
        $user = $store->getUser($xuid);
        $skill = $user->skill;
        if (is_null($skill)) throw new Exception('スキルが設定されていません');

        QuestListener::callSubscribedQuest($p->getXuid(), QuestListener::USE_SKILL, $skill);
        $skill->runSkill($bl->getPosition(), $p);
        $cool_time = $skill->coolTime;
        if (isset(self::$reduceCoolTime[$xuid])) { // クールタイム減らすのを反映
            $cool_time -= self::$reduceCoolTime[$xuid];
        }
        if ($cool_time > 0) { // クールタイムが0以上のときクールタイムの処理をする
            $store->setValue($xuid, "skill_cool_time");
            CoralReefPlugin::$plugin->getScheduler()->scheduleDelayedTask(new ClosureTask(function () use ($store, $xuid, $p): void {
                $store->setValue($xuid, "skill_cool_time", 0);
                if ($p->isOnline()) {
                    $p->sendPopup("スキルのクールタイムが終了しました");
                    $p->getWorld()->addSound($p->getPosition(), new XpLevelUpSound(mt_rand(1, 10)), [$p]);
                }
            }), $cool_time);
        }
    }
}
