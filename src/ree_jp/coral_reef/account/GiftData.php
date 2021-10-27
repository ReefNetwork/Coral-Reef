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
use pocketmine\item\Item;
use ree_jp\coral_reef\sql\SQLConst;
use ree_jp\coral_reef\sql\SQLManager;

class GiftData
{
    public string $from;
    public string $message;
    public int $expiry;
    public array $items = [];
    public array $receivedItems = [];
    public ?string $uniqueID;

    public function __construct(string $fromXuid, string $message, int $expiry, array $items, ?string $id = null, array $receivedItems = [])
    {
        $this->from = $fromXuid;
        $this->message = $message;
        $this->expiry = $expiry;
        $this->uniqueID = $id;
        $this->receivedItems = $receivedItems;

        foreach ($items as $item) {
            if ($item instanceof Item) {
                $item = json_encode($item);
            }
            $key = array_search($item, $this->items, true);
            if ($key === false) {
                $this->items[] = $item;
            } else {
                $this->items[$key]["count"] += $item["count"];
            }
        }
    }

    static function jsonDeserialize(array $arrayItems, ?string $id = null): GiftData
    {
        return new GiftData($arrayItems['from'], $arrayItems['message'], strtotime($arrayItems['expiry']), $arrayItems['items'], $id, $arrayItems['receivedItems']);
    }

    public function jsonSerialize(): array
    {
        return ['from' => $this->from, 'message' => $this->message, 'expiry' => $this->expiry, 'items' => $this->items, 'receivedItems' => $this->receivedItems];
    }

    public function getItems(): array
    {
        $items = [];
        foreach ($this->items as $jsonItem) {
            $items[] = Item::jsonDeserialize(json_decode($jsonItem, true));
        }
        return $items;
    }

    public function isMarkReceived($item): bool
    {
        if ($item instanceof Item) $item = json_encode($item);
        if (!is_string($item)) return false;
        return in_array($item, $this->receivedItems);
    }

    public function markReceived(Item $item): bool
    {
        $jsonItem = json_encode($item);
        if ($this->isMarkReceived($jsonItem)) return false;

        $this->receivedItems[] = $jsonItem;
        return true;
    }

    public function isExpired(): bool
    {
        return ($this->expiry - time()) < 0;
    }

    public function save(string $xuid, ?Closure $func, ?Closure $failure): void
    {
        if (is_null($this->uniqueID)) {
            $id = uniqid();
            // 同じidのギフトがないか確認
            SQLManager::$manager->getValue($xuid, SQLConst::TYPE_GIFT, $id, function (array $rows) use ($func, $failure, $id, $xuid) {
                $row = array_shift($rows);
                if (empty($row)) {
                    SQLManager::$manager->setValue($xuid, SQLConst::TYPE_GIFT, $id, json_encode($this), $func, $failure);
                } else $this->save($xuid, $func, $failure); // あったら最初からもう一回やる
            });
        } else {
            SQLManager::$manager->setValue($xuid, SQLConst::TYPE_GIFT, $this->uniqueID, json_encode($this), $func, $failure);
        }
    }
}
