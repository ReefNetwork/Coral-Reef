<?php
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

use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\Item;
use pocketmine\item\ItemIds;
use pocketmine\Server;
use ree_jp\coral_reef\account\GiftData;
use ree_jp\coral_reef\account\GiftManager;
use ree_jp\coral_reef\gatya\GatyaManager;
use ree_jp\coral_reef\quest\QuestListener;
use ree_jp\coral_reef\sql\SQLConst;
use ree_jp\coral_reef\sql\SQLManager;

class LevelUpQuest extends QuestData
{
    const ID = "level_up";
    const NAME = "レベルアップ";
    const SHORT_DETAILS = "レベルアップしよう!";
    const EXPLANATION = "ブロックを掘ると経験値を入手できます。経験値を一定量集めてサーバーのレベルを上げましょう。";

    function __construct(string $xuid, string $value)
    {
        QuestListener::subscribeQuest($xuid, QuestListener::LEVEL_UP, $this);
        $this->check();
        parent::__construct($xuid, $value);
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
        $user = SQLManager::$manager->getUser($this->xuid);
        if (is_null($user)) return;
        $p = Server::getInstance()->getPlayer($user->name);
        $questLevel = intval($this->value);
        if ($user->level > $questLevel) {
            $questLevel++;
            $this->value = $questLevel;

            SQLManager::$manager->addLog($this->xuid, "quest", self::ID, $questLevel, SQLConst::NOW_TIME, null, null);
            switch ($questLevel) {
                case 1:
                    $item = Item::get(ItemIds::IRON_PICKAXE);
                    $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::UNBREAKING), 3));
                    GatyaManager::addTicket($this->xuid, SQLConst::TICKETS_NORMAL, 1);
                    $this->sendGift($questLevel, [$item]);
                    break;
                case 2:
                    GatyaManager::addTicket($this->xuid, SQLConst::TICKETS_NORMAL, 2);
                    $this->sendGift($questLevel, [Item::get(ItemIds::APPLE, 8)]);
                    break;
                case 3:
                    GatyaManager::addTicket($this->xuid, SQLConst::TICKETS_NORMAL, 2);
                    $this->sendGift($questLevel, [Item::get(ItemIds::APPLE, 16)]);
                    break;
                case 4:
                    GatyaManager::addTicket($this->xuid, SQLConst::TICKETS_NORMAL, 2);
                    $this->sendGift($questLevel, [Item::get(ItemIds::APPLE, 32)]);
                    break;
                case 5:
                    $item = Item::get(ItemIds::IRON_PICKAXE);
                    $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(Enchantment::UNBREAKING), 3));
                    GatyaManager::addTicket($this->xuid, SQLConst::TICKETS_NORMAL, 5);
                    $this->sendGift($questLevel, [$item]);
                    break;
                case 6:
                    GatyaManager::addTicket($this->xuid, SQLConst::TICKETS_NORMAL, 2);
                    break;
            }
            $p->sendMessage("レベルアップ報酬として" . $this->getRewardInfo($questLevel) .
                "を受け取りました\n報酬にアイテムが含まれている場合はギフトから1週間以内に受け取れます\n忘れずにお受けとりください");

            $this->check();
        }
    }

    private function sendGift(int $level, array $items): void
    {
        GiftManager::addGift($this->xuid, new GiftData(0, $level . "レベルのレベルアップ報酬です",
            time() + (7 * 24 * 60 * 60), $items), null, null);
    }

    private function getRewardInfo(int $level): string
    {
        switch ($level) {
            case 1:
                return "ガチャ券×1枚と鉄のツルハシ(耐久3)×1個";
            case 2:
                return "ガチャ券×2枚とりんご×8個";
            case 3:
                return "ガチャ券×2枚とりんご×16個";
            case 4:
                return "ガチャ券×2枚とりんご×32個";
            case 5:
                return "ガチャ券×5枚と鉄のツルハシ(耐久3)×3個";
            case 6:
                return "ガチャ券×3枚";
        }
        return "エラー";
    }

    function getRewardDetails(): string
    {
        $nextLevel = intval($this->value) + 1;
        return "次は" . $nextLevel . "レベルになると" . $this->getRewardInfo($nextLevel) . "が受け取れます";
    }

    function isComplete(): bool
    {
        return false;
    }
}
