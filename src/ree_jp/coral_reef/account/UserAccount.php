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


use Closure;
use Exception;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use ree_jp\coral_reef\quest\QuestManager;
use ree_jp\coral_reef\skill\BreakSkill;
use ree_jp\coral_reef\skill\SkillManager;
use ree_jp\coral_reef\sql\SQLManager;

class UserAccount
{
    const LEVEL_EXPERIMENT = [
        1 => 1, 2 => 1000, 3 => 2500, 4 => 6500, 5 => 11500, 6 => 20500, 7 => 31000, 8 => 43000, 9 => 63250
    ];

    public string $xuid;
    public string $name;
    public int $experience;
    public int $level;
    public int $necessaryExperience;
    public ?BreakSkill $skill;

    function __construct(string $xuid, string $name, int $experience, ?string $skill)
    {
        $this->xuid = $xuid;
        $this->name = $name;
        $this->experience = $experience;
        $this->setLevelAndNecessaryExperience();
        $this->skill = SkillManager::getSkill($skill);
    }

    function save(?Closure $xpFunc = null, ?Closure $skillFunc = null, ?Closure $questFunc = null): void
    {
        if (is_null($this->skill)) {
            $skillId = null;
        } else {
            $skillId = $this->skill->id;
        }
        try {
            SQLManager::$manager->setXp($this->xuid, $this->experience, $xpFunc);
            SQLManager::$manager->setSkill($this->xuid, $skillId, $skillFunc);
            QuestManager::save($this->xuid, $questFunc);
        } catch (Exception $e) {
            Server::getInstance()->getLogger()->error($this->name . 'のデータ保存に失敗しました' . $e->getMessage());
        }
    }

    function addXp(int $xp = 1): void
    {
        $this->experience = $xp + $this->experience;
        $this->necessaryExperience -= $xp;
        if ($this->necessaryExperience <= 0) {
            $beforeLevel = $this->level;
            $this->setLevelAndNecessaryExperience();

            $p = Server::getInstance()->getPlayer($this->name);
            $name = "";
            if (!is_null($p)) {
                $name = $p->getName();
                $p->sendTitle(
                    TextFormat::BLUE . 'L' . TextFormat::GREEN . 'e' . TextFormat::AQUA . 'v' . TextFormat::GREEN . 'e' . TextFormat::BLUE . 'L ' . TextFormat::RED . 'U' . TextFormat::LIGHT_PURPLE . 'P',
                    TextFormat::YELLOW . $beforeLevel . TextFormat::RESET . ' -> ' . TextFormat::GOLD . $this->level);
            }
            $message = $name . "さんのレベルが$this->level になりました";
            Server::getInstance()->broadcastMessage($message);
        }
    }

    private function setLevelAndNecessaryExperience(): void
    {
        foreach (self::LEVEL_EXPERIMENT as $constLevel => $constExperience) {
            if ($constExperience > $this->experience) {
                $this->level = --$constLevel;
                $this->necessaryExperience = $constExperience - $this->experience;
                return;
            }
        }
        $this->level = PHP_INT_MAX;
        $this->necessaryExperience = PHP_INT_MAX;
    }
}
