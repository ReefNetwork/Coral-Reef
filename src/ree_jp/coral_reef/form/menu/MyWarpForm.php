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
use Generator;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use pocketmine\world\Position;
use ree_jp\coral_reef\account\AccountService;
use ree_jp\coral_reef\CoralReefPlugin;
use ree_jp\coral_reef\quest\QuestListener;
use ree_jp\coral_reef\sql\model\WarpPoint;
use ree_jp\coral_reef\sql\repo\WarpRepository;
use ree_jp\coral_reef\sql\RepositoryPool;
use SOFe\AwaitGenerator\Await;

class MyWarpForm
{
    static function sendForm(RepositoryPool $pool, Player $p): void
    {
        Await::f2c(function () use ($p, $pool): Generator {
            /** @var WarpRepository */
            $repo = $pool->get(WarpRepository::class);
            /** @var WarpPoint[] */
            $warps = yield from $repo->getWarps($p->getXuid(), CoralReefPlugin::$serverID);
            $form = (new SimpleForm())
                ->setTitle("Menu -> MyWarp")
                ->setText("自分だけのワープ地点を設定できます");
            foreach ($warps as $warp) {
                $form->addElement(
                    self::createWarpButton($warp,
                        function (Player $p) use ($warp) {
                            $p->sendMessage($warp->warpName . "にワープしています...");
                            AccountService::teleport($p, $warp->pos->getWorld()->getFolderName(), $warp->pos);
                        }
                    )
                );
            }
            $form->addElement(new ClosureButton(
                "ワープ地点を 作成/削除 する", null,
                function (Player $p) use ($pool, $warps) {
                    self::sendMyWarpEditForm($pool, $p, $warps);
                }
            ));
            $p->sendForm($form);
        });
    }

    /**
     * @param RepositoryPool $pool
     * @param Player $p
     * @param WarpPoint[] $warpPoints
     * @return void
     */
    static function sendMyWarpEditForm(RepositoryPool $pool, Player $p, array $warpPoints): void
    {
        /** @var WarpRepository */
        $repo = $pool->get(WarpRepository::class);
        $form = (new SimpleForm())
            ->setTitle("MyWarp -> Edit")
            ->setText("ワープ地点を作成するか削除したいワープ地点を選択してください");
        foreach ($warpPoints as $warp) {
            $form->addElement(
                self::createWarpButton($warp,
                    function (Player $p) use ($pool, $repo, $warp) {
                        $p->sendForm(
                            (new ModalForm(
                                new ClosureButton(
                                    "はい", null,
                                    function () use ($p, $repo, $warp) {
                                        Await::g2c($repo->deleteWarp($warp), function () use ($p): void {
                                            $p->sendMessage("削除しました");
                                        });
                                    }
                                ),
                                new ClosureButton(
                                    "いいえ", null,
                                    function (Player $p) use ($pool, $warp) {
                                        self::sendForm($pool, $p);
                                    }
                                )
                            ))
                                ->setTitle("MyWarpEdit -> Delete")
                                ->setText(TextFormat::RED . "「" . $warp["name"] . "」を本当に削除しますか?\n" .
                                    TextFormat::DARK_RED . "消してしまったら戻すことはできません！")
                        );
                    }
                )
            );
        }
        $form->addElement(
            new ClosureButton(
                "ワープ地点を作成する", null,
                function (Player $p) use ($pool) {
                    self::sendWarpCreateForm($pool, $p);
                }
            )
        );
        $p->sendForm($form);
    }

    static function sendWarpCreateForm(RepositoryPool $pool, Player $p): void
    {
        $nameInput = new Input('作成したいワープ地点の名前を入力してください', '新しいワープ地点');
        $form = new ClosureCustomForm(
            function (Player $p) use ($pool, $nameInput) {
                if (mb_strlen($nameInput->getValue()) < 1) {
                    $p->sendMessage('ワープ地点の名前が短すぎます');
                } else {
                    /** @var WarpRepository */
                    $repo = $pool->get(WarpRepository::class);
                    $repo->setWarp(new WarpPoint($p->getXuid(), $nameInput->getValue(), CoralReefPlugin::$serverID,
                        new Position($p->getPosition()->getFloorX(), $p->getPosition()->getFloorY(),
                            $p->getPosition()->getFloorZ(), $p->getWorld())));
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

    private static function createWarpButton(WarpPoint $warp, Closure $closure): Button
    {
        return new ClosureButton(
            $warp->warpName . "\n" . $warp->pos->getX() . ':' . $warp->pos->getY() . ':' . $warp->pos->getZ(), null,
            $closure
        );
    }
}
