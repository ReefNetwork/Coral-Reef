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

use bbo51dog\bboform\element\Button;
use bbo51dog\bboform\element\ClosureButton;
use bbo51dog\bboform\element\Input;
use bbo51dog\bboform\element\Label;
use bbo51dog\bboform\form\ClosureCustomForm;
use bbo51dog\bboform\form\ModalForm;
use bbo51dog\bboform\form\SimpleForm;
use Closure;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use ree_jp\coral_reef\account\AccountService;
use ree_jp\coral_reef\quest\QuestListener;
use ree_jp\coral_reef\sql\SQLRepository;

class MyWarpForm
{
    static function sendForm(SQLRepository $repo, Player $p): void
    {
        $xuid = $p->getXuid();
        $repo->getWarps($xuid, function (array $rows) use ($repo, $p) {
            $form = (new SimpleForm())
                ->setTitle("Menu -> MyWarp")
                ->setText("自分だけのワープ地点を設定できます");
            foreach ($rows as $warpPoint) {
                $form->addElement(
                    self::createWarpButton($warpPoint,
                        function (Player $p) use ($warpPoint) {
                            $p->sendMessage($warpPoint['name'] . 'にワープしています...');
                            AccountService::teleport($p, $warpPoint['level'], new Vector3($warpPoint['x'], $warpPoint['y'], $warpPoint['z']));
                        }
                    )
                );
            }
            $form->addElement(new ClosureButton(
                "ワープ地点を 作成/削除 する", null,
                function (Player $p) use ($repo, $rows) {
                    self::sendMyWarpEditForm($repo, $p, $rows);
                }
            ));
            $p->sendForm($form);
        });
    }

    static function sendMyWarpEditForm(SQLRepository $repo, Player $p, array $warpPoints): void
    {
        $form = (new SimpleForm())
            ->setTitle("MyWarp -> Edit")
            ->setText("ワープ地点を作成するか削除したいワープ地点を選択してください");
        foreach ($warpPoints as $warpPoint) {
            $form->addElement(
                self::createWarpButton($warpPoint,
                    function (Player $p) use ($repo, $warpPoint) {
                        $p->sendForm(
                            (new ModalForm(
                                new ClosureButton(
                                    "はい", null,
                                    function (Player $p) use ($repo, $warpPoint) {
                                        $repo->deleteWarp($p->getXuid(), $warpPoint['name']);
                                    }
                                ),
                                new ClosureButton(
                                    "いいえ", null,
                                    function (Player $p) use ($repo, $warpPoint) {
                                        self::sendForm($repo, $p);
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
                "ワープ地点を作成する", null,
                function (Player $p) use ($repo) {
                    self::sendWarpCreateForm($repo, $p);
                }
            )
        );
        $p->sendForm($form);
    }

    static function sendWarpCreateForm(SQLRepository $repo, Player $p): void
    {
        $nameInput = new Input('作成したいワープ地点の名前を入力してください', '新しいワープ地点');
        $form = new ClosureCustomForm(
            function (Player $p) use ($repo, $nameInput) {
                if (mb_strlen($nameInput->getValue()) < 1) {
                    $p->sendMessage('ワープ地点の名前が短すぎます');
                } else {
                    $repo->addWarp($p->getXuid(), $nameInput->getValue(),
                        $p->getWorld()->getFolderName(), $p->getPosition()->getFloorX(), $p->getPosition()->getFloorY(),
                        $p->getPosition()->getFloorZ()
                    );
                    QuestListener::callSubscribedQuest($p->getXuid(), QuestListener::CREATE_WARP_POINT, null);
                }
            }
        );
        $form->setTitle("MyWarpEdit -> Create")->addElements(
            new Label("現在の位置にワープ地点を作成します\n重複する名前の場合上書きされます"),
            $nameInput
        );
        $p->sendForm($form);
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
