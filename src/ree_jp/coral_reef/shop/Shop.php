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
use pocketmine\block\BlockIds;
use pocketmine\item\Item;
use pocketmine\level\Position;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\tile\Chest;
use ree_jp\coral_reef\account\GiftData;
use ree_jp\coral_reef\account\GiftManager;
use ree_jp\coral_reef\gatya\GatyaManager;
use ree_jp\coral_reef\money\MoneyService;
use ree_jp\coral_reef\sql\SQLConst;
use ree_jp\coral_reef\sql\SQLManager;

class Shop
{
    public Position $pos;
    public array $payment;

    public function __construct(Position $pos, array $payment)
    {
        $this->pos = $pos;
        $this->payment = $payment;
    }

    static function jsonDeserialize(array $array): Shop
    {
        $level = Server::getInstance()->getLevelByName($array["level"]);
        return new Shop(new Position($array["x"], $array["y"], $array["z"], $level), $array["payment"]);
    }

    #[ArrayShape(["level" => "string", "x" => "int", "y" => "int", "z" => "int", "payment" => "array"])] public function jsonSerialize(): array
    {
        return ["level" => $this->pos->getLevel()->getFolderName(), "x" => $this->pos->getFloorX(), "y" => $this->pos->getFloorY(), "z" => $this->pos->getFloorZ(),
            "payment" => $this->payment];
    }

    public function buy(Player $p, int $count = 1): void
    {
        $xuid = $p->getXuid();
        $this->pay($xuid, $count, function () use ($xuid, $p, $count): void {
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
                GiftManager::addGift($xuid, new GiftData(0, "ショップで購入したアイテムです", time() + (7 * 24 * 60 * 60), $gifts),
                    null, null);
                $p->sendMessage("アイテムの一部がインベントリに入らなかったためギフトに送信しました\n1週間以内に受け取ってください");
            }
            $p->sendMessage("購入しました");
        }, function () use ($p): void {
            $p->sendMessage("購入できませんでした");
        });
    }

    private function pay(string $xuid, int $count, Closure $func, Closure $failure): void
    {
        $value = $this->payment["amount"] * $count;
        switch ($this->payment["type"]) {
            case "money":
                MoneyService::getMoney($xuid, function (int $money) use ($xuid, $func, $failure, $value): void {
                    if ($value <= $money) {
                        MoneyService::reduceMoney($xuid, $value);
                        $func();
                    } else {
                        $failure();
                    }
                });
                break;

            case "normal_tickets":
                SQLManager::$manager->getValue($xuid, SQLConst::TYPE_TICKETS, SQLConst::TICKETS_NORMAL,
                    function (array $rows) use ($xuid, $func, $failure, $value): void {
                        $row = array_shift($rows);
                        if (isset($row['value']) && ($value <= intval($row['value']))) {
                            GatyaManager::addTicket($xuid, SQLConst::TICKETS_NORMAL, -$value, $func);
                        } else {
                            $failure();
                        }
                    });
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
            $nowPos = $this->pos->subtract(0, $i);
            $item = $this->pos->getLevel()->getBlock($nowPos);
            if ($item->getId() !== BlockIds::CHEST) continue;

            $tile = $this->pos->getLevel()->getTile($nowPos);
            if (!$tile instanceof Chest) continue;

            return $tile->getInventory()->getContents();
        }
        return null;
    }
}
