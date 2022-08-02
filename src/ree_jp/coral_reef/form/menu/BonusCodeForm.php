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

namespace ree_jp\coral_reef\form\menu;

use bbo51dog\bboform\element\Input;
use bbo51dog\bboform\element\Label;
use bbo51dog\bboform\form\ClosureCustomForm;
use Closure;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use ree_jp\coral_reef\gatya\GatyaManager;
use ree_jp\coral_reef\sql\mysql\SQLRepository;
use ree_jp\coral_reef\sql\SQLConst;

class BonusCodeForm
{
    static function sendForm(SQLRepository $repo, Player $p): void
    {
        $codeElement = new Input("コードを入力してください", "bonus");
        $p->sendForm((new ClosureCustomForm(function (Player $p) use ($repo, $codeElement): void {

            $code = strtolower($codeElement->getValue());

            if (empty($code)) return;
            self::bonusCode($repo, $p, $code);
        }))->setTitle("Bonus")->addElements(
            new Label("コードを入力するとボーナスを受け取れます\nコードはDiscordやウェブサイトで不定期に配布しています"), $codeElement));
    }

    static function bonusCode(SQLRepository $repo, Player $p, string $code): void
    {
        switch ($code) {
            case "welcome":
                self::useCode($repo, $p, $code, function () use ($repo, $p): void {
                    $p->sendMessage(TextFormat::GREEN . "ReefServerへようこそ");
                    $p->sendMessage(TextFormat::AQUA . "ガチャチケットを" . TextFormat::RED . "3枚" . TextFormat::AQUA . "受け取りました");
                    GatyaManager::addTicket($repo, $p->getXuid(), SQLConst::TICKETS_NORMAL, 3);
                });
                break;
            case "lobby":
                self::useCode($repo, $p, $code, function () use ($repo, $p): void {
                    $p->sendMessage(TextFormat::AQUA . "ガチャチケットを" . TextFormat::RED . "1枚" . TextFormat::AQUA . "受け取りました");
                    GatyaManager::addTicket($repo, $p->getXuid(), SQLConst::TICKETS_NORMAL, 1);
                });
                break;
            case "discord-1000":
                self::useCode($repo, $p, $code, function () use ($repo, $p): void {
                    $p->sendMessage(TextFormat::DARK_PURPLE . "Discordサーバーに入って頂きありがとうございます");
                    $p->sendMessage(TextFormat::AQUA . "ガチャチケットを" . TextFormat::RED . "10枚" . TextFormat::AQUA . "受け取りました");
                    GatyaManager::addTicket($repo, $p->getXuid(), SQLConst::TICKETS_NORMAL, 10);
                });
                break;
            case "summer2022":
                self::useCode($repo, $p, $code, function () use ($repo, $p): void {
                    $p->sendMessage(TextFormat::BLUE . "メンテナンスご協力ありがとうございました");
                    $p->sendMessage(TextFormat::BLUE . "サマー§rガチャチケットを" . TextFormat::RED . "3枚" . TextFormat::RESET . "受け取りました");
                    GatyaManager::addTicket($repo, $p->getXuid(), SQLConst::TICKETS_SUMMER_2022, 3);
                });
                break;
            default:
                $p->sendMessage("そのコードは存在しません");
        }
    }

    private static function useCode(SQLRepository $repo, Player $p, string $code, Closure $func): void
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
