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

namespace ree_jp\coral_reef\task;

use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\scheduler\Task;
use pocketmine\Server;
use ree_jp\coral_reef\sql\SQLConst;
use ree_jp\coral_reef\sql\SQLRepository;

class ServerUpdateTask extends Task
{
    static int $haste_effect = -1; // 効率エフェクト 0がレベル1 0未満はなし
    static int $exp_buff = 1; // 一回掘ると手に入るxp量

    private int $count = 0;

    public function __construct(private SQLRepository $repo)
    {
    }

    public function onRun(): void
    {
        if (self::$haste_effect >= 0) {
            $hasteEffect = new EffectInstance(VanillaEffects::HASTE(), 400, self::$haste_effect, false);
            foreach (Server::getInstance()->getOnlinePlayers() as $p) $p->getEffects()->add($hasteEffect);
        }
        if ($this->count < 6) {
            $this->count++;
            return;
        }

        $this->count = 0;
        $this->repo->getAllSubtypeValue(0, SQLConst::TYPE_ENV, function (array $rows): void { // serverENVを更新する
            foreach ($rows as $row) {
                switch ($row["subtype"]) {
                    case SQLConst::ENV_HASTE_EFFECT:
                        self::$haste_effect = intval($row['value']);
                        break;

                    case SQLConst::ENV_EXP_BUF:
                        self::$exp_buff = intval($row['value']);
                        break;
                }
            }
        });
    }
}
