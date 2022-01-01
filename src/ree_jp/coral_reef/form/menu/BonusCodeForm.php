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

use bbo51dog\bboform\element\Input;
use bbo51dog\bboform\element\Label;
use bbo51dog\bboform\form\ClosureCustomForm;
use Closure;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use ree_jp\coral_reef\gatya\GatyaManager;
use ree_jp\coral_reef\sql\SQLConst;
use ree_jp\coral_reef\sql\SQLManager;

class BonusCodeForm
{
    static function sendForm(SQLManager $repo, Player $p): void
    {
        $codeElement = new Input("コードを入力してください", "bonus");
        $p->sendForm((new ClosureCustomForm(function (Player $p) use ($repo, $codeElement): void {

            $code = strtolower($codeElement->getValue());
            $code = str_replace(["-", "_", " "], "", $code);

            if (empty($code)) return;
            self::bonusCode($repo, $p, $code);
        }))->setTitle("Bonus")->addElements(
            new Label("コードを入力するとボーナスを受け取れます\nコードはDiscordやウェブサイトで不定期に配布しています"), $codeElement));
    }

    static function bonusCode(SQLManager $repo, Player $p, string $code): void
    {
        switch ($code) {
            case "2022":
                self::useCode($repo, $p, $code, function () use ($repo, $p): void {
                    $p->sendMessage(TextFormat::GREEN . "これからもReefServerをよろしくお願いいたします");
                    $p->sendMessage(TextFormat::AQUA . "ガチャチケットを" . TextFormat::RED . "10枚" . TextFormat::AQUA . "受け取りました");
                    GatyaManager::addTicket($repo, $p->getXuid(), SQLConst::TICKETS_NORMAL, 10);
                });
                break;
            case "Cyclone200m":
                self::useCode($repo, $p, $code, function () use ($repo, $p): void {
                    $p->sendMessage(TextFormat::GREEN . "Cyclone0849さんの経験値量が2億を超えました!!!!おめでとう!!!");
                    $p->sendMessage(TextFormat::AQUA . "ガチャチケットを" . TextFormat::RED . "2枚" . TextFormat::AQUA . "受け取りました");
                    GatyaManager::addTicket($repo, $p->getXuid(), SQLConst::TICKETS_NORMAL, 2);
                    $p->sendMessage(TextFormat::RED . "クリスマスガチャチケットを" . TextFormat::RED . "2枚" . TextFormat::AQUA . "受け取りました");
                    GatyaManager::addTicket($repo, $p->getXuid(), SQLConst::TICKETS_CHRISTMAS_2021, 2);
                });
                break;
            default:
                $p->sendMessage("そのコードは存在しません");
        }
    }

    private static function useCode(SQLManager $repo, Player $p, string $code, Closure $func): void
    {
        $repo->getValue($p->getXuid(), SQLConst::TYPE_BONUS, $code, function (array $rows) use ($repo, $func, $p, $code): void {
            $row = array_shift($rows);
            if (!empty($row)) {
                $p->sendMessage("そのコードは使用済みです");
                return;
            }
            $repo->setValue($p->getXuid(), SQLConst::TYPE_BONUS, $code, SQLConst::COMPLETE, function () use ($repo, $func, $code, $p): void {
                $repo->addLog($p->getXuid(), SQLConst::LOG_BONUS, $code, SQLConst::COMPLETE, SQLConst::NOW_TIME, null, null);
                $func();
            });
        }, function () use ($p): void {
            $p->sendMessage("エラーが発生しました");
        });
    }
}
