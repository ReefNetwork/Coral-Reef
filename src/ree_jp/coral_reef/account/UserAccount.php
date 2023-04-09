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
use Generator;
use pocketmine\player\Player;
use pocketmine\scheduler\ClosureTask;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use pocketmine\world\Position;
use pocketmine\world\sound\XpLevelUpSound;
use ree_jp\coral_reef\CoralReefPlugin;
use ree_jp\coral_reef\quest\QuestListener;
use ree_jp\coral_reef\quest\QuestManager;
use ree_jp\coral_reef\skill\BreakSkill;
use ree_jp\coral_reef\skill\SkillManager;
use ree_jp\coral_reef\sql\model\PlayerData;
use ree_jp\coral_reef\sql\model\WarpPoint;
use ree_jp\coral_reef\sql\mysql\SQLRepository;
use ree_jp\coral_reef\sql\repo\PlayerRepository;
use ree_jp\coral_reef\sql\repo\UserRepository;
use ree_jp\coral_reef\sql\repo\WarpRepository;
use ree_jp\coral_reef\sql\RepositoryPool;
use ree_jp\reef_edge\ReefEdgePlugin;
use ree_jp\reef_edge\socket\SocketData;
use ree_jp\reef_edge\socket\SocketService;
use ree_jp\stackstorage\sql\Queue;
use SOFe\AwaitGenerator\Await;

class UserAccount
{
    public string $xuid;
    public string $name;
    public int $experience;
    public int $level;
    public int $necessaryExperience;
    public ?BreakSkill $skill;

    public bool $loaded = false;

    function __construct(string $xuid, string $name, int $experience, ?string $skill)
    {
        $this->xuid = $xuid;
        $this->name = $name;
        $this->experience = $experience;
        $this->setLevelAndNecessaryExperience();
        $skillInst = SkillManager::getSkill($skill);
        if ($skillInst === null || $skillInst->needLevel > $this->level) {
            $this->skill = null;
        } else $this->skill = $skillInst;
    }

    function save(RepositoryPool $pool, Player $p): Generator
    {
        /** @var SQLRepository $sqlRepo */
        $sqlRepo = $pool->get(SQLRepository::class);
        /** @var PlayerRepository $playerRepo */
        $playerRepo = $pool->get(PlayerRepository::class);
        /** @var UserRepository $userRepo */
        $userRepo = $pool->get(UserRepository::class);
        /** @var WarpRepository $warpRepo */
        $warpRepo = $pool->get(WarpRepository::class);

        $await = [];
        try {
            $await[] = $warpRepo->setWarp(new WarpPoint($p->getXuid(), AccountService::autoSaveWarpName(), CoralReefPlugin::$serverID,
                new Position($p->getPosition()->getFloorX(), $p->getPosition()->getFloorY(), $p->getPosition()->getFloorZ(), $p->getWorld())));
            $await[] = $playerRepo->setPlayerData(PlayerData::create($p));
            $await[] = $userRepo->setUserData($this);
            $await[] = Await::promise(fn($func) => QuestManager::save($sqlRepo, $this->xuid, $func));
            $await[] = Queue::doCache($this->xuid);
            yield from Await::all($await);
        } catch (Exception $e) {
            Server::getInstance()->getLogger()->error($this->name . "のデータ保存に失敗しました" . $e->getMessage());
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

            $p->broadcastSound(new XpLevelUpSound(30));
            $p->sendTitle(
                TextFormat::BLUE . 'L' . TextFormat::GREEN . 'e' . TextFormat::AQUA . 'v' . TextFormat::GREEN . 'e' . TextFormat::BLUE . 'L ' .
                TextFormat::RED . 'U' . TextFormat::LIGHT_PURPLE . 'P', TextFormat::YELLOW . $beforeLevel . TextFormat::RESET . ' -> ' .
                TextFormat::GOLD . $this->level);
            $message = $this->name . "さんのレベルが$this->level になりました";
            SocketService::sendBroadcastMessage(ReefEdgePlugin::$socketClient, $message);
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
                $this->updateLevelTag();
                return;
            }
        }
        $this->level = array_key_last(Experiment::LEVEL_EXPERIMENT);
        $this->necessaryExperience = -999;
        CoralReefPlugin::$plugin->getScheduler()->scheduleDelayedTask(new ClosureTask(function (): void {
            try {
                $this->updateLevelTag();
            } catch (Exception $ex) {
                Server::getInstance()->getLogger()->logException($ex);
            }
        }), 20);
    }

    private function updateLevelTag(): void
    {
        if ($this->xuid === "0") return;

        ReefEdgePlugin::$socketClient->send(new SocketData("item-add",
            ["xuid" => $this->xuid, "type" => "info_tag", "subType" => "seichi_level", "item" => "§g{$this->level}レベル", "count" => 1, "isNotDuplicate" => true]),
            function (): void {
                $p = AccountService::getPlayerByXuid($this->xuid);
                $p?->sendMessage("整地レベル称号を$this->level に更新しました");
            }
        );
    }
}
