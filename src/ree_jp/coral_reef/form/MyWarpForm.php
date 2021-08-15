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

use Exception;
use Frago9876543210\EasyForms\elements\Button;
use Frago9876543210\EasyForms\elements\Input;
use Frago9876543210\EasyForms\elements\Label;
use Frago9876543210\EasyForms\forms\CustomForm;
use Frago9876543210\EasyForms\forms\CustomFormResponse;
use Frago9876543210\EasyForms\forms\Form;
use Frago9876543210\EasyForms\forms\MenuForm;
use Frago9876543210\EasyForms\forms\ModalForm;
use pocketmine\level\Position;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use ree_jp\coral_reef\account\AccountManager;
use ree_jp\coral_reef\sql\SQLManager;

class MyWarpForm
{
    static function myWarpForm(Player $p): Form
    {
        $xuid = $p->getXuid();
        $buttons = [];
        try {
            $warps = SQLManager::$manager->getWarps($xuid);
        } catch (Exception $e) {
            Server::getInstance()->getLogger()->error('[MyWarp]' . $p->getName() . 'の処理中に' . $e->getMessage());
            return FormManager::messageForm('エラーが発生しました');
        }
        foreach ($warps as $warpPoint) {
            if (array_key_exists('NAME', $warpPoint) && array_key_exists('LEVEL', $warpPoint) && array_key_exists('X', $warpPoint) && array_key_exists('Y', $warpPoint) && array_key_exists('Z', $warpPoint)) {
                array_push($buttons, new Button($warpPoint['NAME'] . "\n" . $warpPoint['X'] . ':' . $warpPoint['Y'] . ':' . $warpPoint['Z']));
            } else {
                array_push($buttons, new Button('エラーが発生しました'));
            }
        }
        $warpButtons = $buttons;
        $editValue = array_push($buttons, new Button('ワープ地点を 作成/削除 する')) - 1;
        return new MenuForm('Menu -> MyWarp', '自分だけのワープ地点を設定できます', $buttons,
            function (Player $p, Button $button) use ($warps, $warpButtons, $editValue): void {
                if (array_key_exists($button->getValue(), $warps)) {
                    $warpPoint = $warps[$button->getValue()];
                    $p->sendMessage($warpPoint['NAME'] . 'にワープしています...');
                    AccountManager::teleport($p, $warpPoint['LEVEL'], new Position($warpPoint['X'], $warpPoint['Y'], $warpPoint['Z']));
                } elseif ($button->getValue() === $editValue) {
                    $p->sendForm(self::myWarpEditForm($warps, $warpButtons));
                } else $p->sendMessage('エラーが発生しました');
            });
    }

    static function myWarpEditForm(array $warps, array $warpButtons): MenuForm
    {
        $createValue = array_push($warpButtons, new Button('ワープ地点を作成する')) - 1;
        return new MenuForm('MyWarp -> edit', 'ワープ地点を作成するか削除したいワープ地点を選択してください', $warpButtons,
            function (Player $p, Button $button) use ($warps, $createValue): void {
                if (array_key_exists($button->getValue(), $warps)) {
                    $warpPoint = $warps[$button->getValue()];
                    $p->sendForm(new ModalForm('MyWarpEdit -> delete', TextFormat::DARK_RED . $warpPoint['NAME'] . 'を本当に削除しますか?',
                        function (Player $p, bool $result) use ($warpPoint): void {
                            if ($result) {
                                try {
                                    SQLManager::$manager->deleteWarp($p->getXuid(), $warpPoint['NAME']);
                                    $p->sendMessage('ワープ地点を削除しました');
                                } catch (Exception $e) {
                                    Server::getInstance()->getLogger()->error('[MyWarpDelete]' . $p->getName() . 'の処理中に' . $e->getMessage());
                                    $p->sendMessage('エラーが発生しました');
                                }
                            } else $p->sendForm(self::myWarpForm($p));
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
            } else {
                try {
                    SQLManager::$manager->addWarp($p->getXuid(), $input, $p->getLevel()->getFolderName(), $p->getFloorX(), $p->getFloorY(), $p->getFloorZ());
                    $p->sendMessage('ワープ地点を作成しました');
                } catch (Exception $e) {
                    Server::getInstance()->getLogger()->error('[MyWarpCreate]' . $p->getName() . 'の処理中に' . $e->getMessage());
                    $p->sendMessage('エラーが発生しました');
                    return;
                }
            }
        });
    }
}
