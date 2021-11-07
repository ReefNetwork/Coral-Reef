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

namespace ree_jp\coral_reef\form;

use bbo51dog\bboform\element\Input;
use bbo51dog\bboform\element\Label;
use bbo51dog\bboform\form\ClosureCustomForm;
use pocketmine\Player;
use pocketmine\utils\TextFormat;
use poggit\libasynql\SqlError;
use ree_jp\coral_reef\gatya\GatyaManager;
use ree_jp\coral_reef\sql\SQLConst;
use ree_jp\coral_reef\sql\SQLManager;

class BonusCodeForm
{
    static function sendForm(Player $p): void
    {
        $codeElement = new Input("コードを入力してください", "bonus");
        $p->sendForm((new ClosureCustomForm(function (Player $p, ClosureCustomForm $form) use ($codeElement): void {
            $code = strtolower($codeElement->getValue());
            if (empty($code)) return;
            self::bonusCode($p, $code);
        }))->addElements(new Label("コードを入力するとボーナスを受け取れます\nコードはDiscordやウェブサイトで不定期に配布しています"), $codeElement));
    }

    static function bonusCode(Player $p, string $code): void
    {
        $xuid = $p->getXuid();
        switch ($code) {
            case "reef2nd":
                SQLManager::$manager->getValue($xuid, SQLConst::TYPE_BONUS, $code, function (array $rows) use ($p, $code): void {
                    $row = array_shift($rows);
                    if (!empty($row)) {
                        $p->sendMessage("そのコードは使用済みです");
                        return;
                    }
                    SQLManager::$manager->setValue($p->getXuid(), SQLConst::TYPE_BONUS, $code, "use", function () use ($p): void {
                        $p->sendMessage(TextFormat::GREEN . "これからもReefServerをよろしくお願いいたします");
                        $p->sendMessage(TextFormat::AQUA . "ガチャチケットを" . TextFormat::RED . "20枚" . TextFormat::AQUA . "受け取りました");
                        GatyaManager::addTicket($p->getXuid(), SQLConst::TICKETS_NORMAL, 20);
                    });
                }, function (SqlError $error) use ($p): void {
                    $p->sendMessage("エラーが発生しました");
                });
                break;
            default:
                $p->sendMessage("そのコードは存在しません");
        }
    }
}
