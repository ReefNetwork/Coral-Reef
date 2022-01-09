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
use pocketmine\Server;
use poggit\libasynql\SqlError;
use ree_jp\coral_reef\sql\SQLConst;
use ree_jp\coral_reef\sql\SQLRepository;

class GiftService
{
    static function addGift(SQLRepository $repo, string $target, GiftData $gift, ?Closure $func, ?Closure $failure): void
    {
        $gift->save($repo, $target, $func, $failure);
    }

    static function checkAllExpired(SQLRepository $repo, string $xuid): void
    {
        $repo->getAllSubtypeValue($xuid, SQLConst::TYPE_GIFT, function (array $rows) use ($repo, $xuid) {
            foreach ($rows as $row) {
                $gift = GiftData::jsonDeserialize(json_decode($row["value"], true), $row["subtype"]);
                if ($gift->isExpired()) {
                    $repo->deleteValue($xuid, SQLConst::TYPE_GIFT, $gift->uniqueID, null, function (SqlError $error) use ($xuid) {
                        Server::getInstance()->getLogger()->error("[CheckExpired] $xuid の削除中に" . $error->getErrorMessage());
                    });
                }
            }
        });
    }
}
