<?php
/*
 *  CCCCC                        lll RRRRRR                 fff
 * CC    C  oooo  rr rr    aa aa lll RR   RR   eee    eee  ff
 * CC      oo  oo rrr  r  aa aaa lll RRRRRR  ee   e ee   e ffff
 * CC    C oo  oo rr     aa  aaa lll RR  RR  eeeee  eeeee  ff
 *  CCCCC   oooo  rr      aaa aa lll RR   RR  eeeee  eeeee ff
 *
 * Copyright (c) 2021-2021. Ree-jp(https://ree-jp.net)
 */

namespace ree_jp\coral_reef\task;

use pocketmine\scheduler\Task;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use ree_jp\coral_reef\account\AccountManager;
use ree_jp\coral_reef\account\SettingManager;
use ree_jp\coral_reef\sql\SettingConst;

class SendServerTipTask extends Task
{
    const TIPS = ['ReefServerへようこそ', 'Discordサーバー(discord.gg/reef)に入ると最新の情報を受け取れます', '設定でヒントを表示しないようにできます',
        'reef.ree-jp.netで役立つヒントを確認できます', 'ウェブサイトでも操作方法を確認できます', 'スニークをしている間、通常はスキルが無効になります'];

    public function onRun(int $currentTick)
    {
        $tip = self::TIPS[array_rand(self::TIPS)];
        foreach (Server::getInstance()->getOnlinePlayers() as $p) {
            $xuid = $p->getXuid();
            if (!AccountManager::hasValue($xuid, 'tip_cool_time') &&
                !SettingManager::isEnableOption($xuid, SettingConst::HIDE_SERVER_TIP)) {
                $p->sendTip(TextFormat::DARK_GRAY . "ヒント: $tip");
            }
        }
    }
}
