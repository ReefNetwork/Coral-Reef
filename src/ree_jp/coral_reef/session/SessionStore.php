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


namespace ree_jp\coral_reef\session;

use ree_jp\coral_reef\sql\SQLManager;

class SessionStore
{
    /**@var SessionData[] */
    private array $sessions = [];

    public function createSession(string $xuid): void
    {
        $this->sessions[$xuid] = new SessionData($xuid);
    }

    public function destruction(SQLManager $repo, string $xuid): void
    {
        $session = $this->getSessionData($xuid);
        if (!is_null($session)) {
            $session->quit($repo);
            unset($this->sessions[$xuid]);
        }
    }

    public function getSessionData(string $xuid): ?SessionData
    {
        if (isset($this->sessions[$xuid])) {
            return $this->sessions[$xuid];
        }
        return null;
    }
}
