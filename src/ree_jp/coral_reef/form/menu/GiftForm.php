<?php
/*
 *  CCCCC                        lll RRRRRR                 fff
 * CC    C  oooo  rr rr    aa aa lll RR   RR   eee    eee  ff
 * CC      oo  oo rrr  r  aa aaa lll RRRRRR  ee   e ee   e ffff
 * CC    C oo  oo rr     aa  aaa lll RR  RR  eeeee  eeeee  ff
 *  CCCCC   oooo  rr      aaa aa lll RR   RR  eeeee  eeeee ff
 *
 * Copyright (c) 2021-2022. Ree-jp(https://ree-jp.net)
 */

namespace ree_jp\coral_reef\form\menu;

use bbo51dog\bboform\element\ClosureButton;
use bbo51dog\bboform\form\SimpleForm;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use ree_jp\coral_reef\account\AccountStore;
use ree_jp\coral_reef\account\GiftData;
use ree_jp\coral_reef\sql\SQLConst;
use ree_jp\coral_reef\sql\SQLRepository;
use Throwable;

class GiftForm
{
    static function sendForm(SQLRepository $repo, AccountStore $store, Player $p): void
    {
        $repo->getAllSubtypeValue($p->getXuid(), SQLConst::TYPE_GIFT, function (array $rows) use ($store, $repo, $p): void {
            /** @var GiftData[] $gifts */
            $gifts = [];
            /** @var ClosureButton[] $buttons */
            $buttons = [];

            foreach ($rows as $row) {
                $gift = GiftData::jsonDeserialize(json_decode($row["value"], true), $row["subtype"]);
                $buttons[] = new ClosureButton(
                    "送り主: " . $store->getUserName($gift->from) . "(有効期限: " . date("y年m月d日 H時i分", $gift->expiry) .
                    ")\n" . $gift->message,
                    null,
                    function (Player $p) use ($repo, $store, $gift) {
                        self::sendGiftDetailForm($repo, $store, $p, $gift);
                    }
                );
                $gifts[] = $gift;
            }
            $allReceiveButton = new ClosureButton(
                "受け取れるすべてのアイテムを受け取る", null,
                function (Player $p) use ($repo, $gifts) {
                    foreach ($gifts as $gift) {
                        self::receiveItems($repo, $p, $gift);
                    }
                    $p->sendMessage("全てのアイテムを受け取りました");
                }
            );
            $form = (new SimpleForm())
                ->setTitle("Menu -> Gift")
                ->setText("受け取れるプレゼント一覧です")
                ->addElements(...$buttons)
                ->addElement($allReceiveButton);
            $p->sendForm($form);
        }, function () use ($p): void {
            $p->sendMessage("エラーが発生しました");
        });
    }

    static function sendGiftDetailForm(SQLRepository $repo, AccountStore $store, Player $p, GiftData $gift): void
    {
        $itemString = "\n";
        foreach ($gift->getItems() as $item) {
            $color = $gift->isMarkReceived($item) ? TextFormat::DARK_GRAY : TextFormat::GREEN; // 受け取り済みは灰色
            $itemString = $itemString . $color . $item->getName() . "(" . $item->getCount() . "個)" . TextFormat::RESET . "\n";
        }
        $form = (new SimpleForm())
            ->setTitle("Gift -> Detail")
            ->setText("送り主: " . $store->getUserName($gift->from) .
                "\n有効期限: " . date("y年m月d日 H時i分", $gift->expiry) . "\nアイテム(受け取り済みのアイテムは灰色です): " . $itemString .
                "\nメッセージ: " . $gift->message . TextFormat::DARK_GRAY . "\nギフトID: " . $gift->uniqueID)
            ->addElements(
                new ClosureButton(
                    "アイテムを受け取る", null,
                    function (Player $p) use ($repo, $gift) {
                        if (self::receiveItems($repo, $p, $gift)) {
                            $p->sendMessage("全てのアイテムを受け取りました");
                        } else {
                            $p->sendMessage("受け取り済みです");
                        }
                    }
                ),
                new ClosureButton(
                    "戻る", null,
                    function (Player $p) use ($repo, $store) {
                        self::sendForm($repo, $store, $p);
                    }
                ),
                new ClosureButton(
                    "ギフトを削除する", null,
                    function (Player $p) use ($repo, $gift) {
                        if (is_null($gift->uniqueID)) {
                            $p->sendMessage("ギフトIDが不明なため削除出来ませんでした");
                            return;
                        }
                        $repo->deleteValue($p->getXuid(), SQLConst::TYPE_GIFT, $gift->uniqueID, function () use ($p): void {
                            $p->sendMessage("ギフトを削除しました");
                        }, function () use ($p): void {
                            $p->sendMessage("エラーが発生しました");
                        });
                    }
                ),
            );
        $p->sendForm($form);
    }

    static private function receiveItems(SQLRepository $repo, Player $p, GiftData $gift): bool
    {
        foreach ($gift->getItems() as $item) {
            if ($gift->markReceived($item)) {
                if ($p->getInventory()->canAddItem($item)) {
                    $p->sendMessage(TextFormat::DARK_GRAY . $item->getName() . "を受け取りました");
                    $p->getInventory()->addItem($item);
                } else {
                    try {
                        /**
                         * @noinspection PhpUndefinedNamespaceInspection
                         * @noinspection PhpUndefinedClassInspection
                         * @noinspection PhpFullyQualifiedNameUsageInspection
                         */
                        \ree_jp\stackStorage\api\StackStorageAPI::$instance->add($p->getXuid(), $item);
                    } catch (Throwable) { // StackStorageAPIが見つからなかった場合
                        $gift->save($repo, $p->getXuid(), null, null);
                        return false;
                    }
                    $p->sendMessage(TextFormat::DARK_GRAY . $item->getName() . "をストレージで受け取りました");
                }
            } else {
                $gift->save($repo, $p->getXuid(), null, null);
                return false;
            }
        }
        $gift->save($repo, $p->getXuid(), null, null);
        return true;
    }
}
