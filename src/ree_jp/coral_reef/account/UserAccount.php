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
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use ree_jp\coral_reef\skill\BreakSkill;
use ree_jp\coral_reef\skill\SkillManager;
use ree_jp\coral_reef\sql\SQLManager;

class UserAccount
{
    const LEVEL_EXPERIMENT = [
        1 => 0, 2 => 100,
    ];

    public string $xuid;
    public string $name;
    public int $experience;
    public int $level;
    public int $necessaryExperience;
    public BreakSkill $skill;

    public function __construct(string $xuid, string $name, int $experience, string $skill)
    {
        $this->xuid = $xuid;
        $this->name = $name;
        $this->experience = $experience;
        $this->setLevelAndNecessaryExperience();
        $this->skill = SkillManager::getSkill($skill);
    }

    public function save(): void
    {
        try {
            SQLManager::$manager->setXp($this->xuid, $this->experience);
        } catch (Exception $e) {
            Server::getInstance()->getLogger()->error($this->name . 'のデータ保存に失敗しました' . $e->getMessage());
        }
    }

    public function addXp(int $xp = 1): void
    {
        $this->experience = $xp + $this->experience;
        if ($this->necessaryExperience <= $xp) {
            ++$this->level;

            $p = Server::getInstance()->getPlayer($this->name);
            $name = "";
            if (!is_null($p)) {
                $name = $p->getName();
                $p->sendTitle(
                    TextFormat::BLUE . 'L' . TextFormat::GREEN . 'e' . TextFormat::AQUA . 'v' . TextFormat::GREEN . 'e' . TextFormat::BLUE . 'L ' . TextFormat::RED . 'U' . TextFormat::LIGHT_PURPLE . 'P',
                    TextFormat::YELLOW . ($this->level - 1) . TextFormat::RESET . ' -> ' . TextFormat::GOLD . $this->level);
            }
            Server::getInstance()->broadcastMessage($name . "さんのレベルが$this->level になりました");
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
