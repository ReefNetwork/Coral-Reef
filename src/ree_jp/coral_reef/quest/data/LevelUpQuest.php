<?php /** @noinspection PhpDuplicateSwitchCaseBodyInspection */

/*
 *  CCCCC                        lll RRRRRR                 fff
 * CC    C  oooo  rr rr    aa aa lll RR   RR   eee    eee  ff
 * CC      oo  oo rrr  r  aa aaa lll RRRRRR  ee   e ee   e ffff
 * CC    C oo  oo rr     aa  aaa lll RR  RR  eeeee  eeeee  ff
 *  CCCCC   oooo  rr      aaa aa lll RR   RR  eeeee  eeeee ff
 *
 * Copyright (c) 2021. Ree-jp(https://ree-jp.net)
 */

namespace ree_jp\coral_reef\quest\data;

use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\enchantment\VanillaEnchantments;
use pocketmine\item\VanillaItems;
use pocketmine\player\Player;
use pocketmine\Server;
use ree_jp\coral_reef\account\AccountStore;
use ree_jp\coral_reef\account\GiftData;
use ree_jp\coral_reef\account\GiftService;
use ree_jp\coral_reef\gatya\GatyaManager;
use ree_jp\coral_reef\quest\QuestListener;
use ree_jp\coral_reef\sql\mysql\SQLRepository;
use ree_jp\coral_reef\sql\SQLConst;

class LevelUpQuest extends QuestData
{
    const ID = "level_up";
    const NAME = "レベルアップ";
    const SHORT_DETAILS = "レベルアップしよう!";
    const EXPLANATION = "ブロックを掘ると経験値を入手できます。経験値を一定量集めてサーバーのレベルを上げましょう。";

    function __construct(SQLRepository $repo, private AccountStore $store, string $xuid, ?string $value)
    {
        if (is_null($value)) $value = "0";
        parent::__construct($repo, $xuid, $value);
        QuestListener::subscribeQuest($xuid, QuestListener::LEVEL_UP, $this);
        $this->check();
    }

    function onEvent(string $type, $value): void
    {
        switch ($type) {
            case QuestListener::LEVEL_UP:
                $this->check();
                break;
        }
    }

    private function check(): void
    {
        $user = $this->store->getUser($this->xuid);
        if (is_null($user)) return;
        $questLevel = intval($this->value);
        if ($user->level > $questLevel) {
            $questLevel++;
            $this->value = strval($questLevel);

            QuestListener::callSubscribedQuest($this->xuid, QuestListener::CLEAR_QUEST, $this);
            $this->repo->addLog($this->xuid, SQLConst::LOG_QUEST, self::ID, $questLevel, SQLConst::NOW_TIME, null, null);
            switch ($questLevel) {
                case 1:
                    $item = VanillaItems::IRON_PICKAXE();
                    $item->addEnchantment(new EnchantmentInstance(VanillaEnchantments::UNBREAKING(), 3));
                    GatyaManager::addTicket($this->repo, $this->xuid, SQLConst::TICKETS_NORMAL, 10);
                    $this->sendGift($questLevel, [$item]);
                    break;
                case 2:
                    GatyaManager::addTicket($this->repo, $this->xuid, SQLConst::TICKETS_NORMAL, 2);
                    $this->sendGift($questLevel, [VanillaItems::APPLE()->setCount(8)]);
                    break;
                case 3:
                    GatyaManager::addTicket($this->repo, $this->xuid, SQLConst::TICKETS_NORMAL, 2);
                    $this->sendGift($questLevel, [VanillaItems::APPLE()->setCount(16)]);
                    break;
                case 4:
                    GatyaManager::addTicket($this->repo, $this->xuid, SQLConst::TICKETS_NORMAL, 2);
                    $this->sendGift($questLevel, [VanillaItems::APPLE()->setCount(32)]);
                    break;
                case 5:
                    $item = VanillaItems::IRON_PICKAXE();
                    $item->addEnchantment(new EnchantmentInstance(VanillaEnchantments::UNBREAKING(), 3));
                    GatyaManager::addTicket($this->repo, $this->xuid, SQLConst::TICKETS_NORMAL, 5);
                    $this->sendGift($questLevel, [$item]);
                    break;
                default:
                    GatyaManager::addTicket($this->repo, $this->xuid, SQLConst::TICKETS_NORMAL, $this->getGiveTicket($questLevel));
                    break;
            }
            $p = Server::getInstance()->getPlayerExact($user->name);
            if ($p instanceof Player) {
                $p->sendMessage("レベルアップ報酬として" . $this->getRewardDetails($questLevel) .
                    "を受け取りました");
                if ($questLevel <= 5) {
                    $p->sendMessage("報酬にアイテムが含まれている場合はギフトから1週間以内に受け取れます");
                }
            }
            $this->check();
        }
    }

    private function sendGift(int $level, array $items): void
    {
        GiftService::addGift($this->repo, $this->xuid, new GiftData(0, $level . "レベルのレベルアップ報酬です",
            time() + (7 * 24 * 60 * 60), $items), null, null);
    }

    function getProgress(): string
    {
        return (intval($this->value) + 1) . "レベルにレベルアップしよう";
    }

    function getRewardDetails(?int $level = null): string
    {
        if (is_null($level)) $level = intval($this->value) + 1;
        return match ($level) {
            1 => "ガチャ券×10枚と鉄のツルハシ(耐久3)×1個",
            2 => "ガチャ券×2枚とりんご×8個",
            3 => "ガチャ券×2枚とりんご×16個",
            4 => "ガチャ券×2枚とりんご×32個",
            5 => "ガチャ券×5枚と鉄のツルハシ(耐久3)×3個",
            default => "ガチャ券×" . $this->getGiveTicket($level) . "枚",
        };
    }

    private function getGiveTicket(int $level): int
    {
        $give = ceil($level / 5) + 2;
        if ($give > 10) {
            $give = 10;
        }
        if (($level % 5) === 0) $give += 3;
        return $give;
    }

    function isComplete(): bool
    {
        return false;
    }
}
