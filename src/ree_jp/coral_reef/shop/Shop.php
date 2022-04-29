<?php
/*
 *  CCCCC                        lll RRRRRR                 fff
 * CC    C  oooo  rr rr    aa aa lll RR   RR   eee    eee  ff
 * CC      oo  oo rrr  r  aa aaa lll RRRRRR  ee   e ee   e ffff
 * CC    C oo  oo rr     aa  aaa lll RR  RR  eeeee  eeeee  ff
 *  CCCCC   oooo  rr      aaa aa lll RR   RR  eeeee  eeeee ff
 *
 * Copyright (c) 2022. Ree-jp(https://ree-jp.net)
 */

namespace ree_jp\coral_reef\shop;

use pocketmine\world\Position;

abstract class Shop
{
    public Position $pos;
    public int $dayLimit;
    public string $category;
    public string $subCategory;

    /**
     * @var int[]
     */
    public array $dayLimitCounter;

    abstract static function jsonDeserialize(array $array): static;

    abstract public function jsonSerialize(): array;


    public function resetDayLimitCounter(ShopStore $store): void
    {
        $this->dayLimitCounter = [];
        $store->updateShop($this);
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

    public function getDayLimitCounter(string $xuid): int
    {
        $this->checkDayLimitCounterExpired();
        return $this->dayLimitCounter[$xuid] ?? 0;
    }

    public function checkDayLimitCounterExpired(): void
    {
        $todayResetTime = $this->getDayResetTime();
        if (isset($this->dayLimitCounter["expiry"]) && ($this->dayLimitCounter["expiry"] == $todayResetTime)) return;

        $this->dayLimitCounter = ["expiry" => $todayResetTime];
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