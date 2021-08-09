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
use Frago9876543210\EasyForms\forms\Form;
use Frago9876543210\EasyForms\forms\MenuForm;
use Frago9876543210\EasyForms\forms\ModalForm;
use pocketmine\math\Vector3;
use pocketmine\Player;
use pocketmine\Server;
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
            function (Player $p, Button $button) {
                switch ($button->getValue()) {
                    case 0:
                        $p->sendForm($this->worldTeleportForm());
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
                        $p->sendForm($this->myWarpForm($p));
                        break;

                    case 9:
                        $p->sendForm(new MenuForm('Menu -> Setting'));
                        break;

                    default:
                        $p->sendMessage('ページを開けませんでした');
                }
            }));
    }

    private function worldTeleportForm(): MenuForm
    {
        return new MenuForm('Menu -> World', '移動するワールドを選択してください', [new Button('ロビー'), new Button('整地ワールド1')],
            function (Player $p, Button $button) {
                $teleportWorld = function (string $levelName) use ($p) {

                };
                switch ($button->getValue()) {
                    case 0:
                        $this->teleport($p, 'lobby');
                        break;
                    case 1:
                        $this->teleport($p, 'main_1');
                        break;
                    default:
                        $p->sendMessage('エラーが発生しました');
                }
            });
    }

    private function myWarpForm(Player $p): Form
    {
        $xuid = $p->getXuid();
        $buttons = [];
        try {
            $warps = SQLManager::$manager->getWarps($xuid);
        } catch (Exception $e) {
            Server::getInstance()->getLogger()->error('[MyWarp]' . $p->getName() . 'の処理中に' . $e->getMessage());
            return $this->messageForm('エラーが発生しました');
        }
        foreach ($warps as $warpPoint) {
            if (array_key_exists('name', $warpPoint) && array_key_exists('level', $warpPoint) && array_key_exists('x', $warpPoint) && array_key_exists('y', $warpPoint) && array_key_exists('z', $warpPoint)) {
                array_push($buttons, new Button($warpPoint['name'] . '\n' . $warpPoint['x'] . ':' . $warpPoint['y'] . ':' . $warpPoint['z']));
            } else {
                array_push($buttons, new Button('エラーが発生しました'));
            }
        }
        array_push($buttons, new Button('ワープ地点を 作成/編集 する'));
        return new MenuForm('Menu -> MyWarp', '自分だけのワープ地点を設定できます', $buttons,
            function (Player $p, Button $button) use ($warps) {
                if (array_key_exists($button->getValue(), $warps)) {
                    $warpPoint = $warps[$button->getValue()];
                    $this->teleport($p, $warpPoint['level'], new Vector3($warpPoint['x'], $warpPoint['y'], $warpPoint['z']));
                }
            });
    }

    private function teleport(Player $p, string $levelName, Vector3 $vec = null): void
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

    private function messageForm(string $label): ModalForm
    {
        return new ModalForm('メッセージ', $label, function () {
        }, 'ok', 'ok');
    }
}
