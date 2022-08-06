<?php /** @noinspection PhpDocSignatureInspection */

/*
 *  CCCCC                        lll RRRRRR                 fff
 * CC    C  oooo  rr rr    aa aa lll RR   RR   eee    eee  ff
 * CC      oo  oo rrr  r  aa aaa lll RRRRRR  ee   e ee   e ffff
 * CC    C oo  oo rr     aa  aaa lll RR  RR  eeeee  eeeee  ff
 *  CCCCC   oooo  rr      aaa aa lll RR   RR  eeeee  eeeee ff
 *
 * Copyright (c) 2022. Ree-jp(https://ree-jp.net)
 */

namespace ree_jp\coral_reef\sql\repo;

use Generator;
use ree_jp\coral_reef\land\LandData;

interface LandRepository extends Repository
{

    /**
     * @param string $server
     * @return LandData[]
     */
    public function getLands(string $server): Generator;

    /**
     * @param LandData $land
     * @param string $server
     * @return Generator
     */
    public function setLand(LandData $land, string $server): Generator;

    /**
     * @param LandData $land
     * @return Generator
     */
    public function deleteLand(LandData $land): Generator;

    /**
     * @param string $xuid
     * @param string $name
     * @return LandData | null
     */
    public function isExistLand(string $xuid, string $name): Generator;
}