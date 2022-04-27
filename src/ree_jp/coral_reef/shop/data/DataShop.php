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

namespace ree_jp\coral_reef\shop\data;

use pocketmine\Server;
use pocketmine\world\Position;
use ree_jp\coral_reef\shop\Shop;

class DataShop extends Shop
{

    public function __construct(public Position $pos, public DataShopData $data, public int $havaLimit, public string $orderType, public array $payment, public int $dayLimit,
                                public array    $dayLimitCounter, public string $category)
    {
    }

    static function jsonDeserialize(array $array): static
    {
        $level = Server::getInstance()->getWorldManager()->getWorldByName($array["level"]);
        return new DataShop(new Position($array["x"], $array["y"], $array["z"], $level), new DataShopData($array["data_type"], $array["data_sub_type"], $array["data_value"], $array["count"]),
            $array["have_limit"], $array["order_type"], $array["payment"], $array["day_limit"], $array["day_limit_counter"], $array["category"]);
    }

    public function jsonSerialize(): array
    {
        return ["level" => $this->pos->getWorld()->getFolderName(), "x" => $this->pos->getFloorX(), "y" => $this->pos->getFloorY(), "z" => $this->pos->getFloorZ(),
            "data_type" => $this->data->type, "data_sub_type" => $this->data->subtype, "data_value" => $this->data->value, "data_count" => $this->data->count, "hava_limit" => $this->havaLimit,
            "order_type" => $this->orderType, "payment" => $this->payment, "day_limit" => $this->dayLimit, "day_limit_counter" => $this->dayLimitCounter, "category" => $this->category, "type" => "data"];
    }
}

class DataShopData
{
    public function __construct(public string $type, public string $subtype, public string $value, public int $count)
    {
    }
}