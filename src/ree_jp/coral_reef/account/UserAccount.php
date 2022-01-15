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
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use ree_jp\coral_reef\quest\QuestListener;
use ree_jp\coral_reef\quest\QuestManager;
use ree_jp\coral_reef\skill\BreakSkill;
use ree_jp\coral_reef\skill\SkillManager;
use ree_jp\coral_reef\sql\SQLRepository;

class UserAccount
{
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

    function save(SQLRepository $repo, ?Closure $xpFunc = null, ?Closure $skillFunc = null, ?Closure $questFunc = null): void
    {
        if (is_null($this->skill)) {
            $skillId = null;
        } else {
            $skillId = $this->skill->id;
        }
        try {
            $repo->setXp($this->xuid, $this->experience, $xpFunc);
            $repo->setSkill($this->xuid, $skillId, $skillFunc);
            QuestManager::save($repo, $this->xuid, $questFunc);
        } catch (Exception $e) {
            Server::getInstance()->getLogger()->error($this->name . 'のデータ保存に失敗しました' . $e->getMessage());
        }
    }

    function addXp(Player $p, int $xp = 1): void
    {
        $this->experience = $xp + $this->experience;
        $this->necessaryExperience -= $xp;
        if ($this->necessaryExperience <= 0 && ($this->level !== array_key_last(Experiment::LEVEL_EXPERIMENT))) {
            $beforeLevel = $this->level;
            $this->setLevelAndNecessaryExperience();
            QuestListener::callSubscribedQuest($p->getXuid(), QuestListener::LEVEL_UP, $this->level);

            $p->sendTitle(
                TextFormat::BLUE . 'L' . TextFormat::GREEN . 'e' . TextFormat::AQUA . 'v' . TextFormat::GREEN . 'e' . TextFormat::BLUE . 'L ' .
                TextFormat::RED . 'U' . TextFormat::LIGHT_PURPLE . 'P', TextFormat::YELLOW . $beforeLevel . TextFormat::RESET . ' -> ' .
                TextFormat::GOLD . $this->level);
            $message = $this->name . "さんのレベルが$this->level になりました";
            Server::getInstance()->broadcastMessage($message);
        }
    }

    public function setXp(int $xp): void
    {
        $this->experience = $xp;
        $this->setLevelAndNecessaryExperience();
    }

    private function setLevelAndNecessaryExperience(): void
    {
        foreach (Experiment::LEVEL_EXPERIMENT as $constLevel => $constExperience) {
            if ($constExperience > $this->experience) {
                $this->level = --$constLevel;
                $this->necessaryExperience = $constExperience - $this->experience;
                return;
            }
        }
        $this->level = array_key_last(Experiment::LEVEL_EXPERIMENT);
        $this->necessaryExperience = -999;
    }
}
