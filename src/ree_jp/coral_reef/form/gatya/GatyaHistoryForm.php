<?php
/*
 *  CCCCC                        lll RRRRRR                 fff
 * CC    C  oooo  rr rr    aa aa lll RR   RR   eee    eee  ff
 * CC      oo  oo rrr  r  aa aaa lll RRRRRR  ee   e ee   e ffff
 * CC    C oo  oo rr     aa  aaa lll RR  RR  eeeee  eeeee  ff
 *  CCCCC   oooo  rr      aaa aa lll RR   RR  eeeee  eeeee ff
 *
 * Copyright (c) 2022-2022. Ree-jp(https://ree-jp.net)
 */

namespace ree_jp\coral_reef\form\gatya;

use bbo51dog\bboform\element\ClosureButton;
use bbo51dog\bboform\form\SimpleForm;
use Generator;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use ree_jp\coral_reef\CoralReefPlugin;
use ree_jp\coral_reef\form\PageViewForm;
use ree_jp\coral_reef\gatya\items\SpecialItemService;
use ree_jp\coral_reef\sql\model\LogData;
use ree_jp\coral_reef\sql\repo\LogRepository;
use ree_jp\coral_reef\sql\SQLConst;
use SOFe\AwaitGenerator\Await;

class GatyaHistoryForm
{
    const GATYA_LIST = [SQLConst::LOG_GATYA => "ノーマルガチャ", SQLConst::LOG_GATYA_CHRISTMAS_2021 => "クリスマスガチャ2021",
        SQLConst::LOG_GATYA_SUMMER_2022 => "サマーガチャ2022", SQLConst::LOG_GATYA_HALLOWEEN_NIGHT =>
            TextFormat::GOLD . "Halloween" . TextFormat::DARK_PURPLE . "Night" . TextFormat::RESET . "ガチャ", SQLConst::LOG_GATYA_HALLOWEEN_PARTY =>
            TextFormat::GOLD . "Halloween" . TextFormat::DARK_GREEN . "Party" . TextFormat::RESET . "ガチャ",
        SQLConst::LOG_GATYA_SNOW_CANDY => TextFormat::WHITE . "Snow" . TextFormat::RED . "Candy" . TextFormat::RESET . "ガチャ",
        SQLConst::LOG_GATYA_STEAM_PUNK => TextFormat::GRAY . "Steam" . TextFormat::WHITE . "Punk" . TextFormat::RESET . "ガチャ",];

    static function sendForm(Player $p): void
    {
        $form = new SimpleForm();
        $form->setTitle("Gatya -> History");
        foreach (self::GATYA_LIST as $id => $name) {
            $form->addElement(new ClosureButton($name, null,
                function (Player $p) use ($id): void {
                    self::sendHistoryForm($p, $id);
                }
            ));
        }
        $p->sendForm($form);
    }

    static function sendHistoryForm(Player $p, string $id): void
    {
        /** @var LogRepository $repo */
        $repo = CoralReefPlugin::$plugin->pool->get(LogRepository::class);
        Await::f2c(function () use ($id, $p, $repo): Generator {
            if (!$p->isOnline()) return;

            /** @var LogData[] $logs */
            $logs = yield from $repo->getLogNewer($p->getXuid(), $id);
            if (!$p->isOnline()) return;

            $history = [];
            $historyCount = count($logs);
            $lastReef = 0;
            foreach ($logs as $log) {
                $rare = $log->subtype ?? "不明";
                if ($rare === "reef_rare") {
                    $rare = TextFormat::GREEN . $rare . TextFormat::RESET;
                    if ($lastReef === 0) $lastReef = $historyCount;
                }

                $item = SpecialItemService::getRenewItem($p->getXuid(), $log->value, 0, 1, null);
                $history[] = "$historyCount |レア度 [$rare] : アイテム名 [" . $item?->getCustomName() . TextFormat::RESET . "]" .
                    TextFormat::DARK_GRAY . "(" . date(SQLConst::DATE_FORMAT, $log->time) . ")" . TextFormat::RESET;
                $historyCount--;
            }
            PageViewForm::sendForm($p, "GatyaHistory -> $id", "最後に引いたReefToolは" . count($logs) - $lastReef . "回前です",
                $history, 100);
        });
    }
}
