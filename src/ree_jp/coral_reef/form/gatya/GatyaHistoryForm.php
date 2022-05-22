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
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use poggit\libasynql\SqlError;
use ree_jp\coral_reef\form\PageViewForm;
use ree_jp\coral_reef\gatya\items\SpecialItemService;
use ree_jp\coral_reef\sql\mysql\SQLRepository;
use ree_jp\coral_reef\sql\SQLConst;

class GatyaHistoryForm
{
    const GATYA_LIST = [SQLConst::LOG_GATYA => "ノーマルガチャ", SQLConst::LOG_GATYA_CHRISTMAS_2021 => "クリスマスガチャ2021"];

    static function sendForm(Player $p, SQLRepository $repo): void
    {
        $form = new SimpleForm();
        $form->setTitle("Gatya -> History");
        foreach (self::GATYA_LIST as $id => $name) {
            $form->addElement(new ClosureButton($name, null,
                function (Player $p) use ($id, $repo): void {
                    self::sendHistoryForm($p, $repo, $id);
                }
            ));
        }
        $p->sendForm($form);
    }

    static function sendHistoryForm(Player $p, SQLRepository $repo, string $id): void
    {
        $repo->getLog($p->getXuid(), $id, function (array $rows) use ($id, $p): void {
            $history = [];
            $historyCount = count($rows);
            $lastReef = 0;
            foreach ($rows as $row) {
                $rare = $row["subtype"] ?? "不明";
                if ($rare === "reef_rare") {
                    $rare = TextFormat::GREEN . $rare . TextFormat::RESET;
                    if ($lastReef === 0) $lastReef = $historyCount;
                }

                $item = SpecialItemService::getRenewItem($p->getXuid(), $row["value"], 0, null);
                $history[] = "$historyCount |レア度 [$rare] : アイテム名 [" . $item?->getCustomName() . TextFormat::RESET . "]" .
                    TextFormat::DARK_GRAY . "(" . $row["time"] . ")" . TextFormat::RESET;
                $historyCount--;
            }
            PageViewForm::sendForm($p, "GatyaHistory -> $id", "最後に引いたReefToolは" . count($rows) - $lastReef . "回前です",
                $history, 100);
        }, function (SqlError $error) use ($p) {
            if (!$p->isOnline()) return;
            $p->sendMessage("エラーが発生しました");
            Server::getInstance()->getLogger()->error("[GatyaHistory] " . $p->getName() . "さんの処理中に" . $error->getErrorMessage());
        });
    }
}