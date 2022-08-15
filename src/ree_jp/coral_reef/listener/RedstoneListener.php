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
use tedo0627\redstonecircuit\event\BlockPistonExtendEvent;

class RedstoneListener implements Listener
{
    public function __construct(private StoreHouse $store)
    {
    }

    public function onPushPiston(BlockPistonExtendEvent $ev): void
    {
        /** @var LandStore $landStore */
        $landStore = $this->store->get(LandStore::class);

        $piston = $ev->getPiston()->getPosition();
        $pistonLand = LandService::getLand($landStore, $piston);

        foreach ($ev->getMoveBlocks() as $target) {
            if (!$this->check($pistonLand, LandService::getLand($landStore, $target->getPosition()))) {
                $ev->cancel();
                return;
            }
        }
        foreach ($ev->getBreakBlocks() as $target) {
            if (!$this->check($pistonLand, LandService::getLand($landStore, $target->getPosition()))) {
                $ev->cancel();
                return;
            }
        }
    }

    private function check(?LandData $pistonLand, ?LandData $targetLand): bool
    {
        if ($pistonLand === null || $targetLand === null) {
            return ($pistonLand === null && $targetLand === null);
        }

        return ($pistonLand->xuid === $targetLand->xuid && $pistonLand->name === $targetLand->name);
    }
}