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
use Frago9876543210\EasyForms\forms\MenuForm;
use pocketmine\math\AxisAlignedBB;
use pocketmine\Player;
use pocketmine\Server;
use ree_jp\coral_reef\land\LandData;
use ree_jp\coral_reef\land\LandManager;
use ree_jp\coral_reef\sql\SQLManager;

class LandForm
{
    static function landForm(string $xuid): MenuForm
    {
        $buttons = [];
        $lands = LandManager::$instance->getMyLand($xuid);
        foreach ($lands as $land) {
            if ($land instanceof LandData) {
                $button = new Button($land->name);
            } else {
                $button = new Button('エラーが発生しました');
            }
            array_push($buttons, $button);
        }
        $createValue = array_push($buttons, new Button('新しく土地保護を作成する')) - 1;
        return new MenuForm('Menu -> Land', '土地を保護できます', $buttons,
            function (Player $p, Button $button) use ($lands, $createValue) {
                if (isset($lands[$button->getValue()])) {
                    $p->sendForm(self::landEditForm($lands[$button->getValue()]));
                } elseif ($button->getValue() === $createValue) {
                    $p->sendForm(self::landCreateForm());
                } else $p->sendMessage('エラーが発生しました');
            });
    }

    static function landEditForm(LandData $land): MenuForm
    {
        return new MenuForm('Land -> Edit', "土地保護の名前: $land->name\nワールド: $land->level\n");
    }

    static function landCreateForm(string $x1 = '', string $z1 = '', string $x2 = '', string $z2 = ''): CustomForm
    {
        return new CustomForm('Land -> Create', [
            new Label("作成する土地の情報を入力してください\nウェブサイトに詳しく載っています(https://reef.ree-jp.net)"),
            new Input('x座標1', '1', $x1),
            new Input('z座標1', '1', $z1),
            new Input('x座標2', '10', $x2),
            new Input('z座標2', '10', $z2),
        ], function (Player $p, CustomFormResponse $response) {
            list($x1, $z1, $x2, $z2, $name) = $response->getValues();
            if (is_numeric($x1) && is_numeric($z1) && is_numeric($x2) && is_numeric($z2)) {
                if (mb_strlen($name) > 0) {
                    if ($x1 > $x2) {
                        $minX = $x2;
                        $maxX = $x1;
                    } else {
                        $minX = $x1;
                        $maxX = $x2;
                    }
                    if ($z1 > $z2) {
                        $minZ = $z2;
                        $maxZ = $z1;
                    } else {
                        $minZ = $z1;
                        $maxZ = $z2;
                    }
                    $aabb = new AxisAlignedBB($minX, 0, $minZ, $maxX, 0, $maxZ);
                    $land = new LandData($p->getXuid(), $name, $p->getLevel()->getFolderName(), $aabb);
                    try {
                        SQLManager::$manager->addProtectLand($land);
                        $p->sendMessage($land->name . 'の土地保護を作成しました');
                    } catch (Exception $e) {
                        Server::getInstance()->getLogger()->error('[LandCreate]' . $p->getName() . 'の処理中に' . $e->getMessage());
                        $p->sendMessage('エラーが発生しました');
                    }
                } else $p->sendMessage('名前が短すぎます');
            } else $p->sendMessage('座標の欄には数字を入力してください');
        });
    }
}
