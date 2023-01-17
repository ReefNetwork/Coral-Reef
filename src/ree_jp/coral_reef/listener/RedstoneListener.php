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

namespace ree_jp\coral_reef\listener;

use pocketmine\event\Listener;
use ree_jp\coral_reef\land\LandData;
use ree_jp\coral_reef\land\LandService;
use ree_jp\coral_reef\land\LandStore;
use ree_jp\coral_reef\StoreHouse;
use ree_jp\reef_stone\event\BlockRedstoneWirelessSignalUpdate;
use tedo0627\redstonecircuit\event\BlockPistonExtendEvent;
use tedo0627\redstonecircuit\event\BlockPistonRetractEvent;

class RedstoneListener implements Listener
{
    public function __construct(private StoreHouse $store)
    {
    }

    public function onWireless(BlockRedstoneWirelessSignalUpdate $ev): void
    {
        /** @var LandStore $landStore */
        $landStore = $this->store->get(LandStore::class);
        $from = $ev->getBlock();
        $target = $ev->getTarget();

        if (!$this->check(LandService::getLand($landStore, $from->getPosition()), LandService::getLand($landStore, $target->getPosition()))) {
            $ev->cancel();
        }
    }

    public function onPushPiston(BlockPistonExtendEvent $ev): void
    {
        $this->pistonProtect($ev);
    }

    public function onPullPiston(BlockPistonRetractEvent $ev): void
    {
        $this->pistonProtect($ev);
    }

    private function pistonProtect(BlockPistonExtendEvent|BlockPistonRetractEvent $ev): void
    {
        /** @var LandStore $landStore */
        $landStore = $this->store->get(LandStore::class);

        $piston = $ev->getPiston();
        $face = $piston->getPistonArmFace();
        $pistonLand = LandService::getLand($landStore, $piston->getPosition());

        foreach ($ev->getMoveBlocks() as $target) {
            if (!$this->check($pistonLand, LandService::getLand($landStore, $target->getPosition()))) {
                $ev->cancel();
                return;
            }
            if (!$this->check($pistonLand, LandService::getLand($landStore, $target->getSide($face)->getPosition()))) {
                $ev->cancel();
                return;
            }
        }
        foreach ($ev->getBreakBlocks() as $target) {
            if (!$this->check($pistonLand, LandService::getLand($landStore, $target->getPosition()))) {
                $ev->cancel();
                return;
            }
            if (!$this->check($pistonLand, LandService::getLand($landStore, $target->getSide($face)->getPosition()))) {
                $ev->cancel();
                return;
            }
        }
    }

    private function check(?LandData $fromLand, ?LandData $targetLand): bool
    {
        if ($fromLand === null || $targetLand === null) {
            return ($fromLand === null && $targetLand === null);
        }

        return ($fromLand->xuid === $targetLand->xuid && $fromLand->name === $targetLand->name);
    }
}
