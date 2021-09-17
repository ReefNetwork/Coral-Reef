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

namespace ree_jp\coral_reef\form;

use Frago9876543210\EasyForms\elements\Button;
use Frago9876543210\EasyForms\forms\MenuForm;
use Frago9876543210\EasyForms\forms\ModalForm;
use pocketmine\Player;
use ree_jp\coral_reef\gatya\GatyaManager;
use ree_jp\coral_reef\sql\SQLConst;
use ree_jp\coral_reef\sql\SQLManager;

class GatyaForm
{
    static function sendGatyaForm(Player $p): void
    {
        SQLManager::$manager->getAllSubtypeValue($p->getXuid(), SQLConst::TYPE_TICKETS, function (array $rows) use ($p) {
            $normal = $rows[SQLConst::TICKETS_NORMAL] ?? 0;
            $p->sendForm(new MenuForm('Menu -> Gatya', "ノーマルガチャチケット: $normal 個", [new Button('ノーマルガチャ')],
                function (Player $p, Button $button) use ($normal): void {
                    switch ($button->getValue()) {
                        case 0:
                            $after = -$normal;
                            $p->sendForm(new ModalForm('NormalGatya -> Confirm',
                                "ノーマルガチャチケットを1個消費してノーマルガチャを回しますか？\n$normal -> $after",
                                function (Player $p, bool $result): void {
                                    if ($result) {
                                        GatyaManager::normalGatya($p);
                                    } else self::sendGatyaForm($p);
                                }));
                            GatyaManager::normalGatya($p, 1);
                            $p->sendMessage('');
                            break;
                        default:
                            $p->sendMessage('エラーが発生しました');
                    }
                }));
        });
    }
}
