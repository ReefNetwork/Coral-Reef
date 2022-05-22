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
use pocketmine\block\Block;
use pocketmine\block\BlockLegacyIds;
use pocketmine\block\VanillaBlocks;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\world\Position;
use ree_jp\coral_reef\money\MoneyCache;
use ree_jp\coral_reef\money\MoneyService;
use ree_jp\coral_reef\quest\QuestManager;
use ree_jp\coral_reef\session\SessionData;
use ree_jp\coral_reef\skill\BreakSkill;
use ree_jp\coral_reef\skill\SkillManager;
use ree_jp\coral_reef\skill\TreeBreakService;
use ree_jp\coral_reef\sql\model\PlayerData;
use ree_jp\coral_reef\sql\mysql\SQLRepository;
use ree_jp\coral_reef\sql\PlayerRepository;
use ree_jp\coral_reef\sql\RepositoryPool;
use ree_jp\coral_reef\sql\SettingConst;
use ree_jp\coral_reef\task\ServerUpdateTask;
use SOFe\AwaitGenerator\Await;

class AccountService
{
    const STOP_FLY_WORLD = array("lobby", "shop");

    static function userJoin(SQLRepository $repo, AccountStore $store, Player $p): void
    {
        $xuid = $p->getXuid();
        $store->setValue($xuid, "skill_cool_time", 0);

        $repo->setUser($xuid, $p->getName(), $p->getNetworkSession()->getIp());
        QuestManager::updateQuests($repo, $store, $xuid);
        GiftService::checkAllExpired($repo, $xuid);
        SettingManager::updateNickName($repo, $p);
        SettingManager::updateShowCoordinates($repo, $p);
        SettingManager::updateOption($repo, $p, SettingConst::SNEAK_SKILL);
        SettingManager::updateOption($repo, $p, SettingConst::HIDE_SERVER_TIP);
        SettingManager::updateOption($repo, $p, SettingConst::NO_FREEZE_WATER);
        SettingManager::updateOption($repo, $p, SettingConst::BREAK_UNDER_GROUND);
        SettingManager::updateOption($repo, $p, SettingConst::ALLOW_COOL_TIME_DIG);
        SettingManager::updateOption($repo, $p, SettingConst::OFF_COOL_TIME_SOUND);

        self::updateFly($p, $p->getWorld()->getFolderName());
    }

    static function userQuit(RepositoryPool $pool, AccountStore $store, Player $p): void
    {
        $xuid = $p->getXuid();

        if ($store->hasValue($xuid, 'transfer_server')) {
            $store->setValue($xuid, 'transfer_server', 0);
        } else {
            $account = $store->getUser($xuid);
            if (!is_null($account)) {
                Await::g2c($account->save($pool, $p));
            }
        }
        /** @var SQLRepository */
        $sqlRepo = $pool->get(SQLRepository::class);
        MoneyCache::purge($sqlRepo, $xuid);

        // フライを無効にする
        self::updateFly($p, $p->getWorld()->getFolderName(), false);
    }

    /**
     * @param SQLRepository $repo
     * @param AccountStore $store
     * @param Player $p
     * @param Block $bl
     * @param SessionData $session
     * @return void
     * @throws Exception
     */
    static function blockBroken(SQLRepository $repo, AccountStore $store, Player $p, Block $bl, SessionData $session): void
    {
        $xuid = $p->getXuid();
        $user = $store->getUser($xuid);
        $skill = $user->skill;
        $user->addXp($p, ServerUpdateTask::$exp_buff);
        if (!SettingManager::isEnableOption($p->getXuid(), SettingConst::NO_FREEZE_WATER)) { // 水を掘ったら水が消えるように
            if ($bl->getId() === BlockLegacyIds::WATER) {
                $p->getWorld()->setBlock($bl->getPosition(), VanillaBlocks::AIR());
            }
        }

        $session->breakBlock();
        if ($store->hasValue($xuid, 'skill_active') | $store->hasValue($xuid, "tree_cut")) {
            MoneyService::addMoney($repo, $xuid, 1);
        } else {
            $session->runSkill();
            MoneyService::addMoney($repo, $xuid, 10);

            if ($skill instanceof BreakSkill && $p->isSurvival()) {
                if (!$store->hasValue($xuid, 'skill_cool_time') && !($p->isSneaking() &&
                        !SettingManager::isEnableOption($xuid, SettingConst::SNEAK_SKILL))) {
                    if (((($p->getPosition()->getX() - 1) === $bl->getPosition()->getX()) || (($p->getPosition()->getX() + 1) === $bl->getPosition()->getX())) &&
                        ((($p->getPosition()->getZ() - 1) === $bl->getPosition()->getZ()) || (($p->getPosition()->getZ() + 1) === $bl->getPosition()->getZ()))
                        && ($p->getPosition()->getY() - 1 === $bl->getPosition()->getY()) &&
                        SettingManager::isEnableOption($xuid, SettingConst::BREAK_UNDER_GROUND)) {
                        $p->sendPopup("地面にスキルをは発動できません\n設定で変更できます");
                        return;
                    }

                    $handItem = $p->getInventory()->getItemInHand();
                    $handItemTag = $handItem->getNamedTag();
                    if (TreeBreakService::isTree($bl) && $handItemTag->getByte(TreeBreakService::TREE_CUT, 0) === 1) {
                        $store->setValue($xuid, "tree_cut");
                        TreeBreakService::runBreak($p, $handItem, $bl->getPosition());
                        $store->setValue($xuid, "tree_cut", 0);
                    }

                    $store->setValue($xuid, 'skill_active');
                    SkillManager::skillActive($store, $p, $bl);
                    $store->setValue($xuid, 'skill_active', 0);
                }
            }
        }
    }

    static function getPlayerByXuid(string $xuid): ?Player
    {
        foreach (Server::getInstance()->getOnlinePlayers() as $p) {
            if ($p->getXuid() === $xuid) return $p;
        }
        return null;
    }

    static function isOp(Player $p): bool
    {
        return Server::getInstance()->isOp($p->getName());
    }

    static function teleport(Player $p, string $levelName, Vector3 $pos = null): void
    {
        $world = Server::getInstance()->getWorldManager()->getWorldByName($levelName);
        if (is_null($world)) {
            $p->sendMessage('ワールドが見つかりませんでした');
        } else {
            if (is_null($pos)) {
                $pos = $world->getSafeSpawn();
            } else {
                $pos = Position::fromObject($pos, $world);
            }
            var_dump($p->teleport($pos));
        }
    }

    static function updateFly(Player $p, string $world, ?bool $allow = null): void
    {
        if ($p->isCreative()) {
            return;
        }
        if (is_null($allow)) {
            $allow = !in_array($world, AccountService::STOP_FLY_WORLD);
        }
        if ($allow && $p->isSurvival()) {
            $p->setAllowFlight(true);
            $p->setFlying(true);
            $p->sendPopup("このワールドでは飛行できます");
        } else {
            $p->setFlying(false);
            $p->setAllowFlight(false);
            $p->sendPopup("このワールドでは飛行することはできません");
        }
    }

    static function loadPlayerData(RepositoryPool $pool, Player $p): Generator
    {
        /** @var PlayerRepository */
        $repo = $pool->get(PlayerRepository::class);
        $data = yield from $repo->getPlayerData($p->getXuid());
        if (!$data instanceof PlayerData) return;
        $p->getInventory()->setContents($data->inv);
        $p->getArmorInventory()->setContents($data->armorInv);
        $p->getOffHandInventory()->setContents($data->offHandInv);
        $p->getEnderInventory()->setContents($data->enderInv);
        $p->getEffects()->clear();
        foreach ($data->effects as $effect) {
            $p->getEffects()->add($effect);
        }
        $p->setHealth($data->health);
        $p->getHungerManager()->setFood($data->hunger);
        $p->getXpManager()->addXp($data->xp);
    }

    static function warpAutoSavePoint(RepositoryPool $pool, Player $p): Generator
    {
        /** @var SQLRepository */
        $repo = $pool->get(SQLRepository::class);
        $warps = yield from Await::promise(fn($resolve) => $repo->getWarps($p->getXuid(), $resolve));
        foreach ($warps as $warp) {
            if ($warp["name"] != "自動セーブ") continue;
            AccountService::teleport($p, $warp['level'], new Vector3($warp['x'], $warp['y'], $warp['z']));
        }
    }
}
