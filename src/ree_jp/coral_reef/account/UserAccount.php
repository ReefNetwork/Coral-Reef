<?php


namespace ree_jp\coral_reef\account;


class UserAccount
{
    public string $xuid;
    public string $name;
    public int $experiment;

    public function __construct(string $xuid, string $name, int $experiment)
    {
        $this->xuid = $xuid;
        $this->name = $name;
        $this->experiment = $experiment;
    }
}
