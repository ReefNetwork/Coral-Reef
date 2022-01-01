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
        unset($this->shops);
        $this->shops = [];
        $this->config->reload();
        foreach ($this->config->getAll() as $key => $shopData) {
            $this->shops[$key] = Shop::jsonDeserialize($shopData);
        }
    }

    public function findShop(Position $pos): ?Shop
    {
        foreach ($this->shops as $shop) {
            if ($shop->pos->equals($pos)) {
                return $shop;
            }
        }
        return null;
    }

    private function createKey(Position $pos): string
    {
        return $pos->getWorld()->getFolderName() . ":" . $pos->getX() . ":" . $pos->getY() . ":" . $pos->getZ();
    }

    public function createShop(Shop $shop): void
    {
        $this->config->reload();
        $this->config->set($this->createKey($shop->pos), $shop->jsonSerialize());
        try {
            $this->config->save();
        } catch (JsonException $e) {
        }
        $this->loadShop();
    }

    public function removeShop(Position $pos): void
    {
        $this->config->reload();
        $this->config->remove($this->createKey($pos));
        try {
            $this->config->save();
        } catch (JsonException $e) {
        }
        $this->loadShop();
    }
}
