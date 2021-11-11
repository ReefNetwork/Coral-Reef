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

use bbo51dog\bboform\element\Button;
use bbo51dog\bboform\element\ClosureButton;
use bbo51dog\bboform\element\Input;
use bbo51dog\bboform\element\Label;
use bbo51dog\bboform\form\ClosureCustomForm;
use bbo51dog\bboform\form\CustomForm;
use bbo51dog\bboform\form\ModalForm;
use bbo51dog\bboform\form\SimpleForm;
use Closure;
use pocketmine\math\Vector3;
use pocketmine\Player;
use pocketmine\utils\TextFormat;
use ree_jp\coral_reef\account\AccountManager;
use ree_jp\coral_reef\quest\QuestListener;
use ree_jp\coral_reef\sql\SQLManager;

class MyWarpForm
{
    static function sendWarpForm(Player $p): void
    {
        $xuid = $p->getXuid();
        SQLManager::$manager->getWarps($xuid, function (array $rows) use ($p) {
            $form = (new SimpleForm())
                ->setTitle("Menu -> MyWarp")
                ->setText("自分だけのワープ地点を設定できます");
            foreach ($rows as $warpPoint) {
                $form->addElement(
                    self::createWarpButton(
                        $warpPoint,
                        function (Player $p, ClosureButton $button) use ($warpPoint) {
                            $p->sendMessage($warpPoint['name'] . 'にワープしています...');
                            AccountManager::teleport($p, $warpPoint['level'], new Vector3($warpPoint['x'], $warpPoint['y'], $warpPoint['z']));
                        }
                    )
                );
            }
            $form->addElement(new ClosureButton(
                "ワープ地点を 作成/削除 する",
                null,
                function (Player $p, ClosureButton $button) use ($rows) {
                    $p->sendForm(self::myWarpEditForm($p, $rows));
                }
            ));
            $p->sendForm($form);
        });
    }

    static function myWarpEditForm(Player $p, array $warpPoints): SimpleForm
    {
        $form = (new SimpleForm())
            ->setTitle("MyWarp -> Edit")
            ->setText("ワープ地点を作成するか削除したいワープ地点を選択してください");
        foreach ($warpPoints as $warpPoint) {
            $form->addElement(
                self::createWarpButton(
                    $warpPoint,
                    function (Player $p, ClosureButton $button) use ($warpPoint) {
                        $p->sendForm(
                            (new ModalForm(
                                new ClosureButton(
                                    "はい",
                                    null,
                                    function (Player $p, ClosureButton $button) use ($warpPoint) {
                                        SQLManager::$manager->deleteWarp($p->getXuid(), $warpPoint['name']);
                                    }
                                ),
                                new ClosureButton(
                                    "いいえ",
                                    null,
                                    function (Player $p, ClosureButton $button) use ($warpPoint) {
                                        self::sendWarpForm($p);
                                    }
                                )
                            ))
                                ->setTitle("MyWarpEdit -> Delete")
                                ->setText(TextFormat::DARK_RED . $warpPoint["name"] . "を本当に削除しますか?")
                        );
                    }
                )
            );

        }
        $form->addElement(
            new ClosureButton(
                "ワープ地点を作成する",
                null,
                function (Player $p, ClosureButton $button) {
                    $p->sendForm(self::myWarpCreateForm());
                }
            )
        );
        return $form;
    }

    static function myWarpCreateForm(): CustomForm
    {
        $nameInput = new Input('作成したいワープ地点の名前を入力してください', '新しいワープ地点');
        return (new ClosureCustomForm(
            function (Player $p, ClosureCustomForm $form) use ($nameInput) {
                if (mb_strlen($nameInput->getValue()) < 1) {
                    $p->sendMessage('ワープ地点の名前が短すぎます');
                } else {
                    SQLManager::$manager->addWarp(
                        $p->getXuid(),
                        $nameInput->getValue(),
                        $p->getLevel()->getFolderName(),
                        $p->getFloorX(), $p->getFloorY(),
                        $p->getFloorZ()
                    );
                    QuestListener::callSubscribedQuest($p->getXuid(), QuestListener::CREATE_WARP_POINT, null);
                }
            }
        ))
            ->setTitle("MyWarpEdit -> Create")
            ->addElements(
                new Label("現在の位置にワープ地点を作成します\n重複する名前の場合上書きされます"),
                $nameInput
            );
    }

    private static function createWarpButton(array $warpPoint, Closure $closure): Button
    {
        if (array_key_exists('name', $warpPoint) && array_key_exists('level', $warpPoint) && array_key_exists('x', $warpPoint)
            && array_key_exists('y', $warpPoint) && array_key_exists('z', $warpPoint)) {
            return new ClosureButton(
                $warpPoint['name'] . "\n" . $warpPoint['x'] . ':' . $warpPoint['y'] . ':' . $warpPoint['z'],
                null,
                $closure
            );
        } else {
            return new Button("エラーが発生しました");
        }
    }
}
