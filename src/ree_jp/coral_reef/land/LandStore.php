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

use Generator;
use pocketmine\math\Vector3;
use poggit\libasynql\SqlError;
use ree_jp\coral_reef\CoralReefPlugin;
use ree_jp\coral_reef\sql\mysql\SQLRepository;
use ree_jp\coral_reef\sql\repo\LandRepository;
use ree_jp\coral_reef\sql\RepositoryPool;
use ree_jp\coral_reef\sql\SQLConst;
use ree_jp\coral_reef\Store;
use RuntimeException;
use SOFe\AwaitGenerator\Await;

class LandStore implements Store
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

    public function __construct(RepositoryPool $pool)
    {
        /** @var SQLRepository $sqlRepo */
        $sqlRepo = $pool->get(SQLRepository::class);

        // LandKey(土地保護を共有してる人をメモってるやつ)を確認
        $sqlRepo->getAllUserSubtypeValue(SQLConst::TYPE_LAND_KEY, function (array $landKeys) use ($pool): void {


            Await::f2c(function () use ($landKeys, $pool): Generator {
                /** @var LandRepository $landRepo */
                $landRepo = $pool->get(LandRepository::class);

                /** @var LandData[] $lands */
                $lands = yield from $landRepo->getLands(CoralReefPlugin::$serverID);
                if (count($lands) <= 1) throw new RuntimeException("土地保護が読み込めませんでした!!!!!!!!!!!!!!!!!!!!");
                foreach ($lands as $land) {
                    $this->lands[$land->level][] = $land;

                    foreach ($landKeys as $key) {
                        if (($key["xuid"] == $land->xuid) && ($key["subtype"] === CoralReefPlugin::$serverID . ":" . strtolower($land->name)) && !is_null($key["value"])) {
                            foreach (explode(":", $key["value"]) as $member) {
                                $land->addMember($member);
                            }
                        }
                    }
                }
            });
        }, function (SqlError $error) {
            CoralReefPlugin::$plugin->criticalError("土地キー情報を取得中に" . $error->getErrorMessage());
        });
    }

    public function getLands(string $world): array
    {
        return $this->lands[$world] ?? [];
    }

    public function isParty(string $ownerXuid, string $userXuid): bool
    {
        return !empty($this->party[$ownerXuid]) && in_array($userXuid, $this->party[$ownerXuid]);
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
