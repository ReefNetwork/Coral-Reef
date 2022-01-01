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

namespace ree_jp\coral_reef\shop;

use Closure;
use JetBrains\PhpStorm\ArrayShape;
use pocketmine\block\BlockLegacyIds;
use pocketmine\block\tile\Chest;
use pocketmine\item\Item;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\world\Position;
use ree_jp\coral_reef\account\GiftData;
use ree_jp\coral_reef\account\GiftService;
use ree_jp\coral_reef\gatya\GatyaManager;
use ree_jp\coral_reef\money\MoneyService;
use ree_jp\coral_reef\sql\SQLConst;
use ree_jp\coral_reef\sql\SQLManager;

class Shop
{
    public Position $pos;
    public string $orderType;
    public array $payment;

    public function __construct(Position $pos, string $orderType, array $payment)
    {
        $this->pos = $pos;
        $this->orderType = $orderType;
        $this->payment = $payment;
    }

    static function jsonDeserialize(array $array): Shop
    {
        $level = Server::getInstance()->getWorldManager()->getWorldByName($array["level"]);
        $orderType = $array["order_type"] ?? "buy";
        return new Shop(new Position($array["x"], $array["y"], $array["z"], $level), $orderType, $array["payment"]);
    }

    #[ArrayShape(["level" => "string", "x" => "int", "y" => "int", "z" => "int", "order_type" => "string", "payment" => "array"])]
    public function jsonSerialize(): array
    {
        return ["level" => $this->pos->getWorld()->getFolderName(), "x" => $this->pos->getFloorX(), "y" => $this->pos->getFloorY(), "z" => $this->pos->getFloorZ(),
            "order_type" => $this->orderType, "payment" => $this->payment];
    }

    public function buy(SQLManager $repo, Player $p, int $count = 1): void
    {
        $xuid = $p->getXuid();
        $this->pay($repo, $xuid, $count, function () use ($repo, $xuid, $p, $count): void {
            $items = $this->getItems();
            $gifts = [];
            while ($count > 0) {
                foreach ($items as $item) {
                    if ($p->getInventory()->canAddItem($item)) {
                        $p->getInventory()->addItem($item);
                    } else $gifts[] = $item;
                }
                $count--;
            }
            if (!empty($gifts)) {
                GiftService::addGift($repo, $xuid, new GiftData(0, "ショップで購入したアイテムです", time() + (7 * 24 * 60 * 60), $gifts),
                    null, null);
                $p->sendMessage("アイテムの一部がインベントリに入らなかったためギフトに送信しました\n1週間以内に受け取ってください");
            }
            $p->sendMessage("購入しました");
        }, function () use ($p): void {
            $p->sendMessage("購入できませんでした");
        });
    }

    public function sell(SQLManager $repo, Player $p, int $count = 1): void
    {
        foreach ($this->getItems() as $item) {
            $item = $item->setCount($item->getCount() * $count);
            if (!$p->getInventory()->contains($item)) {
                $p->sendMessage("所持してるアイテムが足りなかったため売却できませんでした");
                return;
            }
        }
        $this->pay($repo, $p->getXuid(), $count, function () use ($p, $count): void {
            foreach ($this->getItems() as $item) {
                $item = $item->setCount($item->getCount() * $count);
                $p->getInventory()->removeItem($item);
            }
            $p->sendMessage("売却しました");
        }, function () use ($p): void {
            $p->sendMessage("売却できませんでした");
        }, true);
    }

    private function pay(SQLManager $repo, string $xuid, int $count, Closure $func, Closure $failure, bool $isSell = false): void
    {
        $value = $this->payment["amount"] * $count;
        switch ($this->payment["type"]) {
            case "money":
                if ($isSell) {
                    MoneyService::addMoney($repo, $xuid, $value);
                    $func();
                } else {
                    MoneyService::getMoney($repo, $xuid, function (int $money) use ($repo, $xuid, $func, $failure, $value): void {
                        if ($value <= $money) {
                            MoneyService::reduceMoney($repo, $xuid, $value);
                            $func();
                        } else {
                            $failure();
                        }
                    });
                }
                break;

            case "normal_tickets":
                if ($isSell) {
                    GatyaManager::addTicket($repo, $xuid, SQLConst::TICKETS_NORMAL, $value, $func);
                } else {
                    $repo->getValue($xuid, SQLConst::TYPE_TICKETS, SQLConst::TICKETS_NORMAL,
                        function (array $rows) use ($repo, $xuid, $func, $failure, $value): void {
                            $row = array_shift($rows);
                            if (isset($row['value']) && ($value <= intval($row['value']))) {
                                GatyaManager::addTicket($repo, $xuid, SQLConst::TICKETS_NORMAL, -$value, $func);
                            } else {
                                $failure();
                            }
                        });
                }
                break;
        }
    }

    /**
     * @return Item[]|null
     */
    public function getItems(): ?array
    {
        $i = 0;
        while ($i < 5) {
            $i++;
            $nowPos = $this->pos->subtract(0, $i, 0);
            $item = $this->pos->getWorld()->getBlock($nowPos);
            if ($item->getId() !== BlockLegacyIds::CHEST) continue;

            $tile = $this->pos->getWorld()->getTile($nowPos);
            if (!$tile instanceof Chest) continue;

            return $tile->getInventory()->getContents();
        }
        return null;
    }
}
