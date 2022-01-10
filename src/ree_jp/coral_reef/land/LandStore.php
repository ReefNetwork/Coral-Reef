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

namespace ree_jp\coral_reef\land;

use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Vector3;
use poggit\libasynql\SqlError;
use ree_jp\coral_reef\CoralReefPlugin;
use ree_jp\coral_reef\sql\SQLConst;
use ree_jp\coral_reef\sql\SQLRepository;

class LandStore
{
    /**
     * @var LandData[][]
     */
    public array $lands = [];

    /**
     * @var Vector3[][]
     */
    public array $pos = [];

    /**
     * @var string[][]
     */
    public array $party = [];

    public function __construct(SQLRepository $repo)
    {
        // LandKey(土地保護を共有してる人をメモってるやつ)を確認
        $repo->getAllUserSubtypeValue(SQLConst::TYPE_LAND_KEY, function (array $landKeys) use ($repo): void {
            var_dump($landKeys);
            $repo->loadProtectLand(function (array $rows) use ($repo, $landKeys) {
                foreach ($rows as $arrayLand) {
                    $level = $arrayLand['level'];
                    if (!isset($this->lands[$level])) $this->lands[$level] = [];

                    $land = new LandData($arrayLand['xuid'], $arrayLand['name'], $level,
                        new AxisAlignedBB($arrayLand['sx'], 0, $arrayLand['sz'], $arrayLand['mx'], 0, $arrayLand['mz']));
                    $this->lands[$level][] = $land;

                    foreach ($landKeys as $key) {
                        var_dump($repo->server . ":" . $land->name);
                        if (($key["xuid"] === $land->xuid) && ($key["subtype"] === $repo->server . ":" . $land->name) && !is_null($key["value"])) {
                            foreach (explode(":", $key["value"]) as $member) {
                                $land->addMember($member);
                            }
                        }
                    }
                }
            }, function (SqlError $error) {
                CoralReefPlugin::$plugin->criticalError("土地情報を取得中に" . $error->getErrorMessage());
            });
        }, function (SqlError $error) {
            CoralReefPlugin::$plugin->criticalError("土地キー情報を取得中に" . $error->getErrorMessage());
        });

    }

    public function isParty(string $ownerXuid, string $userXuid): bool
    {
        return !empty($this->party[$ownerXuid]) && in_array($userXuid, $this->party[$userXuid]);
    }

    public function addParty(string $ownerXuid, string $userXuid): void
    {
        $this->party[$ownerXuid][] = $userXuid;
    }

    public function deleteParty(string $ownerXuid, string $userXuid): void
    {
        array_splice($this->party[$ownerXuid], $userXuid);
    }

    public function allPartyMember($ownerXuid): array
    {
        return $this->party[$ownerXuid] ?? [];
    }
}
