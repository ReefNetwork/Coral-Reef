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

use JsonException;
use pocketmine\utils\Config;
use pocketmine\world\Position;
use ree_jp\coral_reef\CoralReefPlugin;
use ree_jp\coral_reef\shop\data\DataShop;
use ree_jp\coral_reef\shop\item\ItemShop;

class ShopStore
{
    /** @var Shop[] */
    private array $shops;

    private Config $config;

    public function __construct(string $folder)
    {
        $this->config = new Config($folder . "shop.json", Config::JSON);
        $this->config->setJsonOptions(JSON_PRETTY_PRINT);
        $this->loadShop();
    }

    private function loadShop(): void
    {
        $this->shops = [];
        $this->config->reload();
        foreach ($this->config->getAll() as $key => $shopData) {
            $this->shops[$key] = match ($shopData["type"] ?? "data") {
                "item" => ItemShop::jsonDeserialize($shopData),
                "data" => DataShop::jsonDeserialize($shopData)
            };
        }
    }

    public function findShop(Position $pos): ?Shop
    {
        $key = ShopService::createKey($pos);
        return $this->shops[$key] ?: null;
    }

    public function updateShop(Shop $shop): void
    {
        $this->config->set(ShopService::createKey($shop->pos), $shop->jsonSerialize());
        try {
            $this->config->save();
        } catch (JsonException $e) {
            CoralReefPlugin::$plugin->getLogger()->warning("ショップの更新に失敗しました:" . $e->getMessage());
        }
    }

    public function createShop(Shop $shop): void
    {
        $this->config->set(ShopService::createKey($shop->pos), $shop->jsonSerialize());
        try {
            $this->config->save();
        } catch (JsonException $e) {
            CoralReefPlugin::$plugin->getLogger()->warning("ショップの作成に失敗しました:" . $e->getMessage());
        }
        $this->loadShop();
    }

    public function removeShop(Position $pos): void
    {
        $this->config->remove(ShopService::createKey($pos));
        try {
            $this->config->save();
        } catch (JsonException $e) {
            CoralReefPlugin::$plugin->getLogger()->warning("ショップの削除に失敗しました:" . $e->getMessage());
        }
        $this->loadShop();
    }
}
