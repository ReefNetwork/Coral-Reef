<?php

use alemiz\sga\protocol\StarGatePacket;
use alemiz\sga\protocol\types\PacketHelper;
use ree_jp\coral_reef\proxy\ProxyPackets;

class ProxyCommandExecutePacket extends StarGatePacket
{
    public string $playerName;
    public string $command;

    public function encodePayload(): void
    {
        PacketHelper::writeString($this, $this->playerName);
        PacketHelper::writeString($this, $this->command);
    }

    public function decodePayload(): void
    {
        $this->playerName = PacketHelper::readString($this);
        $this->command = PacketHelper::readString($this);
    }

    public function getPacketId(): int
    {
        return ProxyPackets::COMMAND_EXECUTE;
    }
}
