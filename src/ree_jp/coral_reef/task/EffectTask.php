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

use pocketmine\entity\Effect;
use pocketmine\entity\EffectInstance;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\Player;
use pocketmine\scheduler\Task;
use pocketmine\Server;
use ree_jp\coral_reef\gatya\ReefItems;
use ree_jp\coral_reef\skill\SkillManager;

class EffectTask extends Task
{
    /**
     * @inheritDoc
     */
    public function onRun(int $currentTick)
    {
        foreach (Server::getInstance()->getOnlinePlayers() as $p) {
            self::updateEffect($p);
        }
    }

    static function updateEffect(Player $p): void
    {
        $contexts = [];
        for ($slot = 0; $slot <= 3; $slot++) {
            $item = $p->getArmorInventory()->getItem($slot);
            $effectTag = $item->getNamedTagEntry(ReefItems::SPECIAL_EFFECT);
            if (!$effectTag instanceof CompoundTag) continue;
            $contexts = self::checkContext($effectTag, $contexts);
            self::setEffect($p, Effect::NIGHT_VISION, $effectTag->getInt("night_vision", -1));
            self::setEffect($p, Effect::SATURATION, $effectTag->getInt("saturation", -1));
            self::setEffect($p, Effect::JUMP_BOOST, $effectTag->getInt("jump_boost", -1));
            self::setEffect($p, Effect::SPEED, $effectTag->getInt("speed", -1));
        }
        self::contextReflect($p, $contexts);
    }

    private static function checkContext(CompoundTag $tag, array $contexts): array // 属性をチェック
    {
        $context = $tag->getString("context", "");
        if (!empty($context)) {
            if (isset($contexts[$context])) {
                $contexts[$context]++;
            } else $contexts[$context] = 1;
        }
        return $contexts;
    }

    private static function setEffect(Player $p, int $effect, int $level): void
    {
        if ($level < 0) return;
        $p->addEffect(new EffectInstance(Effect::getEffect($effect), 30 * 20, $level));
    }

    private static function contextReflect(Player $p, array $contexts): void // 属性を反映させる
    {
        $xuid = $p->getXuid();
        SkillManager::reduceCoolTime($xuid, 0);
        foreach ($contexts as $context => $value) {
            switch ($context) {
                case "reef_armor":
                    if ($value >= 4) {
                        SkillManager::reduceCoolTime($xuid, 60);
                    }
                    break;
            }
        }
    }
}
