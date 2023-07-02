<?php
/*
 *  CCCCC                        lll RRRRRR                 fff
 * CC    C  oooo  rr rr    aa aa lll RR   RR   eee    eee  ff
 * CC      oo  oo rrr  r  aa aaa lll RRRRRR  ee   e ee   e ffff
 * CC    C oo  oo rr     aa  aaa lll RR  RR  eeeee  eeeee  ff
 *  CCCCC   oooo  rr      aaa aa lll RR   RR  eeeee  eeeee ff
 *
 * Copyright (c) 2023. Ree-jp(https://ree-jp.net)
 */

namespace ree_jp\coral_reef\account;

use pocketmine\entity\Entity;
use pocketmine\network\mcpe\protocol\AddActorPacket;
use pocketmine\network\mcpe\protocol\BossEventPacket;
use pocketmine\network\mcpe\protocol\types\entity\Attribute;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataCollection;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataFlags;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataProperties;
use pocketmine\network\mcpe\protocol\types\entity\PropertySyncData;
use pocketmine\player\Player;
use pocketmine\utils\SingletonTrait;

class BossBarAPI
{
    use SingletonTrait;

    public const COLOR_PINK = 0;
    public const COLOR_BLUE = 1;
    public const COLOR_RED = 2;
    public const COLOR_GREEN = 3;
    public const COLOR_YELLOW = 4;
    public const COLOR_PURPLE = 5;
    public const COLOR_WHITE = 6;
    private array $id = [];
    private array $players = [];

    public function sendBossBar(Player $player, string $title = '', int $channel = 0, float $percent = 1.0, int $color = self::COLOR_PURPLE): void
    {
        $this->hideBossBar($player, $channel);
        $network = $player->getNetworkSession();
        if (!$this->isData($player, $channel)) {
            $metadata = new EntityMetadataCollection();
            $metadata->setGenericFlag(EntityMetadataFlags::FIRE_IMMUNE, true);
            $metadata->setGenericFlag(EntityMetadataFlags::SILENT, true);
            $metadata->setGenericFlag(EntityMetadataFlags::INVISIBLE, true);
            $metadata->setGenericFlag(EntityMetadataFlags::NO_AI, true);
            $metadata->setString(EntityMetadataProperties::NAMETAG, '');
            $metadata->setFloat(EntityMetadataProperties::SCALE, 0.0);
            $metadata->setLong(EntityMetadataProperties::LEAD_HOLDER_EID, -1);
            $metadata->setFloat(EntityMetadataProperties::BOUNDING_BOX_WIDTH, 0.0);
            $metadata->setFloat(EntityMetadataProperties::BOUNDING_BOX_HEIGHT, 0.0);
            $this->players[$player->getId()][$channel] = true;
            $pk = AddActorPacket::create(
                $this->id[$channel] ?? $this->id[$channel] = Entity::nextRuntimeId(),
                $this->id[$channel], EntityIds::SLIME, $player->getPosition(),
                null, 0.0, 0.0, 0.0, 0.0,
                [new Attribute(\pocketmine\entity\Attribute::HEALTH, 0.0, 100.0, 100.0, 100.0, [])],
                $metadata->getAll(), new PropertySyncData([], []), []
            );
            $network->sendDataPacket($pk);
        }
        $pk = BossEventPacket::show($this->id[$channel], $title, $percent);
        $pk->color = $color;
        $network->sendDataPacket($pk);
    }

    public function hideBossBar(Player $player, int $channel = 0): void
    {
        if ($this->isData($player, $channel)) {
            $player->getNetworkSession()->sendDataPacket(BossEventPacket::hide($this->id[$channel]));
        }
    }

    private function isData(Player $player, int $channel): bool
    {
        return isset($this->players[$player->getId()][$channel]);
    }

    public function setTitle(Player $player, string $title, int $channel = 0): void
    {
        if ($this->isData($player, $channel)) {
            $player->getNetworkSession()->sendDataPacket(BossEventPacket::title($this->id[$channel], $title));
        }
    }

    public function setPercent(Player $player, float $percent, int $channel = 0): void
    {
        if ($this->isData($player, $channel)) {
            $player->getNetworkSession()->sendDataPacket(BossEventPacket::healthPercent($this->id[$channel], $percent));
        }
    }

    public function deleteData(Player $player): void
    {
        if (isset($this->players[$player->getId()])) {
            $this->hideBossBar($player);
            unset($this->players[$player->getId()]);
        }
    }
}
