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
use poggit\libasynql\SqlError;
use ree_jp\coral_reef\form\PageViewForm;
use ree_jp\coral_reef\gatya\items\SpecialItemService;
use ree_jp\coral_reef\sql\SQLConst;
use ree_jp\coral_reef\sql\SQLRepository;

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
            foreach ($rows as $row) {
                $item = SpecialItemService::getRenewItem($p->getXuid(), $id, 0, null);

                $history[] = "レア度 [" . $row["subtype"] ?? "不明" . "] : アイテム名 [" . $item?->getCustomName() . "]";
            }
            PageViewForm::sendForm($p, "GatyaHistory -> $id", "", $history, 100);
        }, function (SqlError $error) use ($p) {
            $p->sendMessage("エラーが発生しました");
            Server::getInstance()->getLogger()->error("[GatyaHistory] " . $p->getName() . "さんの処理中に" . $error->getErrorMessage());
        });
    }
}