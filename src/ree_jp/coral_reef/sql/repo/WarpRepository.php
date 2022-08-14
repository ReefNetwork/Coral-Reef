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
use ree_jp\coral_reef\sql\model\WarpPoint;

interface WarpRepository extends Repository
{
    /**
     * @param string $xuid
     * @param string $server
     * @return WarpPoint[]
     */
    public function getWarps(string $xuid, string $server): Generator;

    /**
     * @param WarpPoint $warp
     * @return Generator
     */
    public function setWarp(WarpPoint $warp): Generator;

    /**
     * @param WarpPoint $warp
     * @return Generator
     */
    public function deleteWarp(WarpPoint $warp): Generator;

    /**
     * @param string $xuid
     * @param string $name
     * @return WarpPoint | null
     */
    public function isExistWarp(string $xuid, string $name): Generator;
}