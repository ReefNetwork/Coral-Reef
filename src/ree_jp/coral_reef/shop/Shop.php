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

use JetBrains\PhpStorm\ArrayShape;
use pocketmine\block\BlockLegacyIds;
use pocketmine\block\tile\Chest;
use pocketmine\item\Item;
use pocketmine\Server;
use pocketmine\world\Position;

class Shop
{
    public Position $pos;
    public string $orderType;
    public array $payment;
    public int $dayLimit;

    /**
     * @var int[]
     */
    public array $dayLimitCounter;

    public function __construct(Position $pos, string $orderType, array $payment, int $dayLimit, array $dayLimitCounter)
    {
        $this->pos = $pos;
        $this->orderType = $orderType;
        $this->payment = $payment;
        $this->dayLimit = $dayLimit;
        $this->dayLimitCounter = $dayLimitCounter;
    }

    static function jsonDeserialize(array $array): Shop
    {
        $level = Server::getInstance()->getWorldManager()->getWorldByName($array["level"]);
        return new Shop(new Position($array["x"], $array["y"], $array["z"], $level), $array["order_type"] ?? "buy",
            $array["payment"] ?? "money", $array["day_limit"] ?? 0, $array["day_limit_counter"] ?? []);
    }

    #[ArrayShape(["level" => "string", "x" => "int", "y" => "int", "z" => "int", "order_type" => "string", "payment" => "array",
        "day_limit" => "int", "day_limit_counter" => "array|int[]"])]
    public function jsonSerialize(): array
    {
        return ["level" => $this->pos->getWorld()->getFolderName(), "x" => $this->pos->getFloorX(), "y" => $this->pos->getFloorY(), "z" => $this->pos->getFloorZ(),
            "order_type" => $this->orderType, "payment" => $this->payment, "day_limit" => $this->dayLimit, "day_limit_counter" => $this->dayLimitCounter];
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

    public function resetDayLimitCounter(ShopStore $store): void
    {
        $this->dayLimitCounter = [];
        $store->updateShop($this);
    }

    public function checkDayLimitCounterExpired(): void
    {
        $todayResetTime = $this->getDayResetTime();
        if (isset($this->dayLimitCounter["expiry"]) && ($this->dayLimitCounter["expiry"] == $todayResetTime)) return;

        $this->dayLimitCounter = ["expiry" => $todayResetTime];
    }

    public function getDayLimitCounter(string $xuid): int
    {
        $this->checkDayLimitCounterExpired();
        return $this->dayLimitCounter[$xuid] ?? 0;
    }

    public function addDayLimitCounter(ShopStore $store, string $xuid, int $count): bool
    {
        if ($this->dayLimit <= 0) return true;
        $after = $this->getDayLimitCounter($xuid) + $count;
        if ($after > $this->dayLimit) {
            return false;
        } else {
            $this->dayLimitCounter[$xuid] = $after;
            $store->updateShop($this);
            return true;
        }
    }

    private function getDayResetTime(): int
    {
        // 5時でリセットする
        if (date("H") < 5) { //5時を下回っていたらその日の5時
            return strtotime("today 5hour");
        } else { // 5時より上だったら次の日の5時
            return strtotime("tomorrow 5hour");
        }
    }
}
