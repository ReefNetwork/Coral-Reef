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
use pocketmine\math\Vector3;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use ree_jp\coral_reef\account\AccountManager;
use ree_jp\coral_reef\sql\SQLManager;

class FormManager
{
    const STOP_FLY_WORLD = array('lobby');

    static function sendMenu(Player $p): void
    {
        $xuid = $p->getXuid();
        if (AccountManager::hasValue($xuid, 'form_cool_time')) return;
        AccountManager::setValue($xuid, 'form_cool_time', 10);

        $level = 'loading';
        $necessaryExperience = 'loading';
        try {
            $user = SQLManager::$manager->getUser($xuid);
            $level = $user->level;
            $necessaryExperience = $user->necessaryExperience;
        } catch (Exception $e) {
            Server::getInstance()->getLogger()->error('ユーザーデータの取得中に' . $e->getMessage());
        }

        if (AccountManager::hasValue($xuid, 'fly')) {
            $fly_status = '無効';
        } else {
            $fly_status = '有効';
        }
        $p->sendForm(new MenuForm('ReefServer Menu', "レベル : $level \nレベルアップまで : $necessaryExperience",
            [new Button('ワールド移動'), new Button("飛行を$fly_status にする"), new Button('ワープ地点'), new Button('設定')],
            function (Player $p, Button $button): void {
                switch ($button->getValue()) {
                    case 0:
                        $p->sendForm(self::worldTeleportForm());
                        break;

                    case 1:
                        $xuid = $p->getXuid();
                        if (AccountManager::hasValue($xuid, 'fly')) {
                            AccountManager::setValue($xuid, 'fly', 0);
                            $p->setFlying(false);
                            $p->setAllowFlight(false);
                            $p->sendMessage('飛行を無効にしました');
                        } else {
                            if (in_array($p->getLevel()->getFolderName(), self::STOP_FLY_WORLD)) {
                                $p->sendMessage('このワールドで飛行することはできません');
                                return;
                            }
                            AccountManager::setValue($xuid, 'fly');
                            $p->setAllowFlight(true);
                            $p->setFlying(true);
                            $p->sendMessage('飛行を有効にしました');
                        }
                        break;

                    case 2:
                        $p->sendForm(self::myWarpForm($p));
                        break;

                    case 9:
                        $p->sendForm(new MenuForm('Menu -> Setting'));
                        break;

                    default:
                        $p->sendMessage('ページを開けませんでした');
                }
            }));
    }

    static function worldTeleportForm(): MenuForm
    {
        return new MenuForm('Menu -> World', '移動するワールドを選択してください', [new Button('ロビー'), new Button('整地ワールド1')],
            function (Player $p, Button $button): void {
                switch ($button->getValue()) {
                    case 0:
                        self::teleport($p, 'lobby');
                        break;
                    case 1:
                        self::teleport($p, 'main_1');
                        break;
                    default:
                        $p->sendMessage('エラーが発生しました');
                }
            });
    }

    static function myWarpForm(Player $p): Form
    {
        $xuid = $p->getXuid();
        $buttons = [];
        try {
            $warps = SQLManager::$manager->getWarps($xuid);
        } catch (Exception $e) {
            Server::getInstance()->getLogger()->error('[MyWarp]' . $p->getName() . 'の処理中に' . $e->getMessage());
            return self::messageForm('エラーが発生しました');
        }
        foreach ($warps as $warpPoint) {
            if (array_key_exists('name', $warpPoint) && array_key_exists('level', $warpPoint) && array_key_exists('x', $warpPoint) && array_key_exists('y', $warpPoint) && array_key_exists('z', $warpPoint)) {
                array_push($buttons, new Button($warpPoint['name'] . '\n' . $warpPoint['x'] . ':' . $warpPoint['y'] . ':' . $warpPoint['z']));
            } else {
                array_push($buttons, new Button('エラーが発生しました'));
            }
        }
        $warpButtons = $buttons;
        $editValue = array_push($buttons, new Button('ワープ地点を 作成/削除 する'));
        return new MenuForm('Menu -> MyWarp', '自分だけのワープ地点を設定できます', $buttons,
            function (Player $p, Button $button) use ($warps, $warpButtons, $editValue): void {
                if (array_key_exists($button->getValue(), $warps)) {
                    $warpPoint = $warps[$button->getValue()];
                    $p->sendMessage($warpPoint['name'] . 'にワープしています...');
                    self::teleport($p, $warpPoint['level'], new Vector3($warpPoint['x'], $warpPoint['y'], $warpPoint['z']));
                } elseif ($button->getValue() === $editValue) {
                    $p->sendForm(self::myWarpEditForm($warps, $warpButtons));
                } else $p->sendMessage('エラーが発生しました');
            });
    }

    static function myWarpEditForm(array $warps, array $warpButtons): MenuForm
    {
        $createValue = array_push($warpButtons, new Button('ワープ地点を作成する'));
        return new MenuForm('MyWarp -> edit', 'ワープ地点を作成するか削除したいワープ地点を選択してください', $warpButtons,
            function (Player $p, Button $button) use ($warps, $createValue): void {
                if (array_key_exists($button->getValue(), $warps)) {
                    $warpPoint = $warps[$button->getValue()];
                    $p->sendForm(new ModalForm('MyWarpEdit -> delete', TextFormat::DARK_RED . $warpPoint['name'] . 'を本当に削除しますか?',
                        function (Player $p, bool $result) use ($warpPoint): void {
                            if ($result) {
                                try {
                                    SQLManager::$manager->deleteWarp($p->getXuid(), $warpPoint['name']);
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
            new Label('現在の位置にワープ地点を作成します\n重複する名前の場合上書きされます'),
            new Input('作成したいワープ地点の名前を入力してください', '新しいワープ地点')
        ], function (Player $p, CustomFormResponse $result): void {
            $input = $result->getInput()->getValue();
            if (mb_strlen($input) < 1) {
                $p->sendMessage('ワープ地点の名前が短すぎます');
            } else {
                try {
                    SQLManager::$manager->addWarp($p->getXuid(), $input, $p->getLevel()->getFolderName(), $p->getFloorX(), $p->getFloorY(), $p->getFloorZ());
                } catch (Exception $e) {
                    Server::getInstance()->getLogger()->error('[MyWarpCreate]' . $p->getName() . 'の処理中に' . $e->getMessage());
                    $p->sendMessage('エラーが発生しました');
                    return;
                }
            }
        });
    }

    static function teleport(Player $p, string $levelName, Vector3 $vec = null): void
    {
        if (in_array($levelName, self::STOP_FLY_WORLD) && AccountManager::hasValue($p->getXuid(), 'fly')) {
            AccountManager::setValue($p->getXuid(), 'fly', 0);
            $p->setFlying(false);
            $p->setAllowFlight(false);
            $p->sendMessage('このワールドで飛行することはできません');
        }
        $level = Server::getInstance()->getLevelByName($levelName);
        if (is_null($level)) {
            $p->sendMessage('ワールドが見つかりませんでした');
        } else {
            if (is_null($vec)) {
                $vec = $level->getSpawnLocation();
            }
            $p->setPosition($vec);
        }
    }

    static function messageForm(string $label): ModalForm
    {
        return new ModalForm('メッセージ', $label, function (): void {
        }, 'ok', 'ok');
    }
}
