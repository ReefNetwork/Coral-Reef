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

use Frago9876543210\EasyForms\elements\Button;
use Frago9876543210\EasyForms\forms\MenuForm;
use pocketmine\Player;
use pocketmine\utils\TextFormat;
use poggit\libasynql\SqlError;
use ree_jp\coral_reef\account\AccountManager;
use ree_jp\coral_reef\account\GiftData;
use ree_jp\coral_reef\sql\SQLConst;
use ree_jp\coral_reef\sql\SQLManager;

class GiftForm
{
    static function sendGiftForm(Player $p): void
    {
        SQLManager::$manager->getAllSubtypeValue($p->getXuid(), SQLConst::TYPE_GIFT, function (array $rows) use ($p): void {
            $gifts = [];
            $buttons = [];
            foreach ($rows as $row) {
                $gift = GiftData::jsonDeserialize(json_decode($row["value"], true), $row["subtype"]);
                $buttons[] = new Button("送り主: " . AccountManager::getUserName($gift->from) . "(有効期限: " . date("y年m月d日 H時i分") .
                    ")\n" . $gift->message);
                $gifts[] = $gift;
            }
            $allReceive = array_push($buttons, new Button("受け取れるすべてのアイテムを受け取る"));
            $p->sendForm(new MenuForm("Menu -> Gift", "受け取れるプレゼント一覧です", $buttons,
                function (Player $p, Button $button) use ($allReceive, $gifts): void {
                    if (isset($gifts[$button->getValue()])) {
                        $gift = $gifts[$button->getValue()];
                        $p->sendForm(self::giftDetailForm($gift));
                    } elseif ($button->getValue() === $allReceive) {
                        foreach ($gifts as $gift) {
                            if (!self::receiveItems($p, $gift)) {
                                $p->sendMessage("インベントリが満杯ため一部のアイテムが受け取れませんでした");
                                return;
                            }
                        }
                        $p->sendMessage("全てのアイテムを受け取りました");
                    } else {
                        $p->sendMessage("エラーが発生しました");
                    }
                }));
        }, function (SqlError $error) use ($p): void {
            $p->sendMessage("エラーが発生しました");
        });
    }

    static function giftDetailForm(GiftData $gift): MenuForm
    {
        $itemString = "\n";
        foreach ($gift->getItems() as $item) {
            $color = $gift->isMarkReceived($item) ? TextFormat::DARK_GRAY : TextFormat::GREEN; // 受け取り済みは灰色
            $itemString = $itemString . $color . $item->getName() . "(" . $item->getCount() . "個)" . TextFormat::RESET . "\n";
        }
        return new MenuForm("Gift -> Detail", "送り主: " . AccountManager::getUserName($gift->from) .
            "\n有効期限: " . date("y年m月d日 H時i分") . "\nアイテム(受け取り済みのアイテムは灰色です): " . $itemString .
            "\nメッセージ: " . $gift->message . TextFormat::DARK_GRAY . "\nギフトID: " . $gift->uniqueID,
            [new Button("アイテムを受け取る"), new Button("戻る"), new Button("ギフトを削除する")],
            function (Player $p, Button $button) use ($gift): void {
                switch ($button->getValue()) {
                    case 0:
                        if (self::receiveItems($p, $gift)) {
                            $p->sendMessage("全てのアイテムを受け取りました");
                        } else {
                            $p->sendMessage("インベントリが満杯ため一部のアイテムが受け取れませんでした");
                        }
                        break;
                    case 1:
                        self::sendGiftForm($p);
                        break;
                    case 2:
                        if (is_null($gift->uniqueID)) {
                            $p->sendMessage("ギフトIDが不明なため削除出来ませんでした");
                            return;
                        }
                        SQLManager::$manager->deleteValue($p->getXuid(), SQLConst::TYPE_GIFT, $gift->uniqueID, function () use ($p): void {
                            $p->sendMessage("ギフトを削除しました");
                        }, function (SqlError $error) use ($p): void {
                            $p->sendMessage("エラーが発生しました");
                        });
                        break;
                    default:
                        $p->sendMessage("エラーが発生しました");
                }
            });
    }

    static private function receiveItems(Player $p, GiftData $gift): bool
    {
        foreach ($gift->getItems() as $item) {
            if ($p->getInventory()->canAddItem($item) && $gift->markReceived($item)) {
                $p->sendMessage(TextFormat::DARK_GRAY . $item->getName() . "を受け取りました");
                $p->getInventory()->addItem($item);
            } else {
                SQLManager::$manager->setValue($p->getXuid(), SQLConst::TYPE_GIFT, $gift->uniqueID, json_encode($gift), null);
                return false;
            }
        }
        SQLManager::$manager->setValue($p->getXuid(), SQLConst::TYPE_GIFT, $gift->uniqueID, json_encode($gift), null);
        return true;
    }
}
