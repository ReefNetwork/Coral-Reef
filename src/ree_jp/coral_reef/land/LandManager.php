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

namespace ree_jp\coral_reef\land;

use Exception;
use pocketmine\level\Position;
use pocketmine\math\AxisAlignedBB;
use ree_jp\coral_reef\sql\SQLManager;

class LandManager
{
    static LandManager $instance;

    /**
     * @var LandData[]
     */
    private array $lands = [];

    /**
     * @throws Exception
     */
    public function __construct()
    {
        $arrayLands = SQLManager::$manager->getAllProtectLand();
        foreach ($arrayLands as $arrayLand) {
            if (!(isset($arrayLand['XUID']) && isset($arrayLand['NAME']) && isset($arrayLand['LEVEL']) && isset($arrayLand['MX']) && isset($arrayLand['SX']) &&
                isset($arrayLand['MZ']) && isset($arrayLand['SZ']))) throw new Exception('土地の情報が不足しています');
            array_push($this->lands, new LandData($arrayLand['XUID'], $arrayLand['NAME'], $arrayLand['LEVEL'],
                new AxisAlignedBB($arrayLand['SX'], 0, $arrayLand['SZ'], $arrayLand['MX'], 0, $arrayLand['MZ'])));
        }
    }

    public function getLand(Position $pos): ?LandData
    {
        foreach ($this->lands as $land) {
            if ($land->isLand($pos)) {
                return $land;
            }
        }
        return null;
    }
}
