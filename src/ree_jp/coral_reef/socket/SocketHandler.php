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

namespace ree_jp\coral_reef\socket;

use Closure;

class SocketHandler
{
    /**
     * @var Closure[]
     */
    private array $handlers = [];

    public function registerHandler(string $identity, Closure $func): void
    {
        $this->handlers[$identity] = $func;
    }

    public function handle(string $json): void
    {
        $content = json_decode($json, true);
        if (is_array($content) && isset($content["identity"]) && isset($content["data"]) && isset($this->handlers[$content["identity"]])) {
            $this->handlers[$content["identity"]]($content["data"]);
        }
    }
}
