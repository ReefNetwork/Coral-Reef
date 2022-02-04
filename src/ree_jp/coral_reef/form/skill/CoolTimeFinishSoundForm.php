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

namespace ree_jp\coral_reef\form\skill;

use bbo51dog\bboform\form\SimpleForm;
use pocketmine\player\Player;

class CoolTimeFinishSoundForm
{
    static function sendForm(Player $p): void
    {
        $form = (new SimpleForm())
            ->setTitle("SkillSetting -> Sound")
            ->setText("変更したい");

        $p->sendForm($form);
    }
}