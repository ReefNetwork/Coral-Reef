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
use ree_jp\coral_reef\account\SettingManager;
use ree_jp\coral_reef\sql\SettingConst;

class SendServerTipTask extends Task
{
    const TIPS = ['ReefServerへようこそ', 'Discordサーバー(discord.gg/reef)に入ると最新の情報を受け取れます',
        'reef.ree-jp.netで役立つヒントを確認できます', 'ウェブサイトでも操作方法を確認できます', 'サーバーの動作が遅い場合、サーバーを移動することで改善される可能性があります',
        '手持ちのアイテムを別のサーバーに持ち越すにはストレージを使用してください', 'どのサーバーにいてもチャットをやり取りできます',
        '通常、クールタイム中はブロックが掘れません', '通常、スニークをしている間はスキルが無効になります',
        '設定でヒントを表示しないようにできます', '設定でスキルの暴発を防ぐことができます', '設定でクールタイム中でもブロックが掘れるように出来ます',
        '設定で地面のブロックを間違えて掘った時にスキルを発動させないように出来ます'];

    private int $timer = 99;
    private int $key;

    public function onRun(int $currentTick)
    {
        if ($this->timer > 10) {
            $this->key = array_rand(self::TIPS);
            $this->timer = 0;
        }
        $this->timer++;
        $tip = self::TIPS[$this->key];
        foreach (Server::getInstance()->getOnlinePlayers() as $p) {
            $xuid = $p->getXuid();
            if (!SettingManager::isEnableOption($xuid, SettingConst::HIDE_SERVER_TIP)) {
                $p->sendTip(TextFormat::DARK_GRAY . "ヒント: $tip");
            }
        }
    }
}
