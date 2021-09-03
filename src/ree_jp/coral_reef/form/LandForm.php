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
use pocketmine\item\Item;
use pocketmine\item\ItemIds;
use pocketmine\math\Vector3;
use pocketmine\Player;
use pocketmine\Server;
use ree_jp\coral_reef\account\AccountManager;
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
            function (Player $p, Button $button) use ($lands, $createValue): void {
                if (isset($lands[$button->getValue()])) {
                    $p->sendForm(self::landEditForm($lands[$button->getValue()]));
                } elseif ($button->getValue() === $createValue) {
                    $p->getInventory()->addItem(Item::get(ItemIds::CLOCK)->setLore(['土地保護を設定します']));
                    $p->sendMessage('時計を地面にクリックすることで土地保護を設定できます');
                } else $p->sendMessage('エラーが発生しました');
            });
    }

    static function landEditForm(LandData $land): MenuForm
    {
        return new MenuForm('Land -> Edit', "土地保護の名前: $land->name\nワールド: $land->level\n");
    }

    static function landCreateAssistForm(string $xuid, Vector3 $vec3): MenuForm
    {
        $x1 = '設定されていません';
        $z1 = '設定されていません';
        if (isset(LandManager::$pos[$xuid][1]) && LandManager::$pos[$xuid][1] instanceof Vector3) {
            $storeVec = LandManager::$pos[$xuid][1];
            $x1 = $storeVec->getFloorX();
            $z1 = $storeVec->getFloorZ();
        }
        $x2 = '設定されていません';
        $z2 = '設定されていません';
        if (isset(LandManager::$pos[$xuid][2]) && LandManager::$pos[$xuid][2] instanceof Vector3) {
            $storeVec = LandManager::$pos[$xuid][2];
            $x2 = $storeVec->getFloorX();
            $z2 = $storeVec->getFloorZ();
        }
        return new MenuForm('Land Create Assist', "クリックした場所に地点を設定して土地保護を作成できます\n
        シフト中に時計をクリックすると指定した範囲を確認することもできます", [
            new Button('土地保護を作成する'), new Button('地点1を設定する'), new Button('地点2を設定する')],
            function (Player $p, Button $button) use ($vec3, $x1, $z1, $x2, $z2) {
                $xuid = $p->getXuid();
                $value = $button->getValue();
                switch ($value) {
                    case 0:
                        $p->sendForm(self::landCreateForm($x1, $z1, $x2, $z2));
                        break;
                    case 1:
                    case 2:
                        LandManager::$pos[$xuid][$value] = $vec3;
                        $p->sendMessage("地点$value を設定しました");
                        break;
                    default:
                        $p->sendMessage('エラーが発生しました');
                }
            });
    }

    static function landCreateForm(string $x1 = '', string $z1 = '', string $x2 = '', string $z2 = ''): CustomForm
    {
        return new CustomForm('Land -> Create', [
            new Label("作成する土地の情報を入力してください"),
            new Input('x座標1', '1', $x1),
            new Input('z座標1', '1', $z1),
            new Input('x座標2', '10', $x2),
            new Input('z座標2', '10', $z2),
        ], function (Player $p, CustomFormResponse $response): void {
            list($x1, $z1, $x2, $z2, $name) = $response->getValues();
            if (is_numeric($x1) && is_numeric($z1) && is_numeric($x2) && is_numeric($z2)) {
                $x1 = intval($x1);
                $z1 = intval($z1);
                $x2 = intval($x2);
                $z2 = intval($z2);
                if (mb_strlen($name) > 0) {
                    $aabb = LandManager::$instance->getAabb($x1, $z1, $x2, $z2);
                    $land = new LandData($p->getXuid(), $name, $p->getLevel()->getFolderName(), $aabb);
                    $result = LandManager::$instance->canCreateLand($aabb);
                    if (is_null($result)) {
                        try {
                            SQLManager::$manager->addProtectLand($land);
                            $p->sendMessage($land->name . 'の土地保護を作成しました');
                        } catch (Exception $e) {
                            Server::getInstance()->getLogger()->error('[LandCreate]' . $p->getName() . 'の処理中に' . $e->getMessage());
                            $p->sendMessage('エラーが発生しました');
                        }
                    } else {
                        $name = AccountManager::getUserName($land->xuid);
                        $p->sendMessage("指定した土地の1部が$name さんの$land->name とかぶっていたため土地を作成することが出来ませんでした");
                    }
                } else $p->sendMessage('名前が短すぎます');
            } else $p->sendMessage('座標の欄には数字を入力してください');
        });
    }
}
