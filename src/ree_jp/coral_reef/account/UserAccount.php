<?php


namespace ree_jp\coral_reef\account;


class UserAccount
{
    public string $xuid;
    public string $name;
    public string $nowIp;
    public array $ips;
    public array $deviceIds;

    public function __construct(string $xuid, string $name, string $nowIp, array $ips, array $deviceIds)
    {
        $this->xuid = $xuid;
        $this->name = $name;
        $this->nowIp = $nowIp;
        $this->ips = $ips;
        $this->deviceIds = $deviceIds;
    }
}
