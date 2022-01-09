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
