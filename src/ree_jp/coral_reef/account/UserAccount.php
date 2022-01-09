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
    const LEVEL_EXPERIMENT = [ // 16から18まではスキルで35ブロック破壊と計算する
        1 => 1, 2 => 1000, 3 => 2500, 4 => 6500, 5 => 11500, 6 => 20500, 7 => 31000, 8 => 43000, 9 => 63250, 10 => 85750,
        11 => 135250, 12 => 189250, 13 => 247750, 14 => 342250, 15 => 443500, 16 => 548500, 17 => 656000, 18 => 766000, 19 => 879000, 20 => 995000,
        21 => 1114500, 22 => 1237500, 23 => 1364000, 24 => 1494500, 25 => 1629000, 26 => 1768500, 27 => 1913000, 28 => 2062500, 29 => 2218000, 30 => 2379500,
        31 => 2548000, 32 => 2724000, 33 => 2908000, 34 => 3101000, 35 => 3304000, 36 => 3519500, 37 => 3750000, 38 => 3980500, 39 => 4248500, 40 => 4538000,
        41 => 4852500, 42 => 5194500, 43 => 5566500, 44 => 5971500, 45 => 6412500, 46 => 6893000, 47 => 7416500, 48 => 7986500, 49 => 8606500, 50 => 9281000,
        51 => 10015500, 52 => 10817500, 53 => 11694500, 54 => 12659000, 55 => 13723500, 56 => 14913500, 57 => 16254500, 58 => 17772000, 59 => 19491500,
        60 => 21438500
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
        if ($this->necessaryExperience <= 0 && ($this->level !== array_key_last(self::LEVEL_EXPERIMENT))) {
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
        foreach (self::LEVEL_EXPERIMENT as $constLevel => $constExperience) {
            if ($constExperience > $this->experience) {
                $this->level = --$constLevel;
                $this->necessaryExperience = $constExperience - $this->experience;
                return;
            }
        }
        $this->level = array_key_last(self::LEVEL_EXPERIMENT);
        $this->necessaryExperience = -999;
    }
}
