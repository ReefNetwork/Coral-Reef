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

use Frago9876543210\EasyForms\elements\Button;
use Frago9876543210\EasyForms\elements\Input;
use Frago9876543210\EasyForms\elements\Label;
use Frago9876543210\EasyForms\forms\CustomForm;
use Frago9876543210\EasyForms\forms\CustomFormResponse;
use Frago9876543210\EasyForms\forms\MenuForm;
use Frago9876543210\EasyForms\forms\ModalForm;
use pocketmine\level\Position;
use pocketmine\Player;
use pocketmine\utils\TextFormat;
use ree_jp\coral_reef\account\AccountManager;
use ree_jp\coral_reef\sql\SQLManager;

class MyWarpForm
{
    static function sendWarpForm(Player $p): void
    {
        $xuid = $p->getXuid();
        SQLManager::$manager->getWarps($xuid, function (array $rows) use ($p) {
            $buttons = [];
            foreach ($rows as $warpPoint) {
                if (array_key_exists('name', $warpPoint) && array_key_exists('level', $warpPoint) && array_key_exists('x', $warpPoint)
                    && array_key_exists('y', $warpPoint) && array_key_exists('z', $warpPoint)) {
                    array_push($buttons, new Button($warpPoint['name'] . "\n" . $warpPoint['x'] . ':' . $warpPoint['y'] . ':' . $warpPoint['z']));
                } else {
                    array_push($buttons, new Button('エラーが発生しました'));
                }
            }
            $warpButtons = $buttons;
            $editValue = array_push($buttons, new Button('ワープ地点を 作成/削除 する')) - 1;
            $p->sendForm(new MenuForm('Menu -> MyWarp', '自分だけのワープ地点を設定できます', $buttons,
                function (Player $p, Button $button) use ($rows, $warpButtons, $editValue): void {
                    if (array_key_exists($button->getValue(), $rows)) {
                        $warpPoint = $rows[$button->getValue()];
                        $p->sendMessage($warpPoint['name'] . 'にワープしています...');
                        AccountManager::teleport($p, $warpPoint['level'], new Position($warpPoint['x'], $warpPoint['y'], $warpPoint['z']));
                    } elseif ($button->getValue() === $editValue) {
                        $p->sendForm(self::myWarpEditForm($rows, $warpButtons));
                    } else $p->sendMessage('エラーが発生しました');
                }));
        });
    }

    static function myWarpEditForm(array $warps, array $warpButtons): MenuForm
    {
        $createValue = array_push($warpButtons, new Button('ワープ地点を作成する')) - 1;
        return new MenuForm('MyWarp -> edit', 'ワープ地点を作成するか削除したいワープ地点を選択してください', $warpButtons,
            function (Player $p, Button $button) use ($warps, $createValue): void {
                if (array_key_exists($button->getValue(), $warps)) {
                    $warpPoint = $warps[$button->getValue()];
                    $p->sendForm(new ModalForm('MyWarpEdit -> delete', TextFormat::DARK_RED . $warpPoint['name'] . 'を本当に削除しますか?',
                        function (Player $p, bool $result) use ($warpPoint): void {
                            if ($result) {
                                SQLManager::$manager->deleteWarp($p->getXuid(), $warpPoint['name']);
                            } else self::sendWarpForm($p);
                        }));
                } elseif ($button->getValue() === $createValue) {
                    $p->sendForm(self::myWarpCreateForm());
                } else $p->sendMessage('エラーが発生しました');
            });
    }

    static function myWarpCreateForm(): CustomForm
    {
        return new CustomForm('MyWarpEdit -> Create', [
            new Label("現在の位置にワープ地点を作成します\n重複する名前の場合上書きされます"),
            new Input('作成したいワープ地点の名前を入力してください', '新しいワープ地点')
        ], function (Player $p, CustomFormResponse $result): void {
            $input = $result->getInput()->getValue();
            if (mb_strlen($input) < 1) {
                $p->sendMessage('ワープ地点の名前が短すぎます');
            } else SQLManager::$manager->addWarp($p->getXuid(), $input, $p->getLevel()->getFolderName(), $p->getFloorX(), $p->getFloorY(), $p->getFloorZ());
        });
    }
}
