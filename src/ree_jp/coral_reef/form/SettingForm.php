<?php
/*
 *  CCCCC                        lll RRRRRR                 fff
 * CC    C  oooo  rr rr    aa aa lll RR   RR   eee    eee  ff
 * CC      oo  oo rrr  r  aa aaa lll RRRRRR  ee   e ee   e ffff
 * CC    C oo  oo rr     aa  aaa lll RR  RR  eeeee  eeeee  ff
 *  CCCCC   oooo  rr      aaa aa lll RR   RR  eeeee  eeeee ff
 *
 * Copyright (c) 2021. Ree-jp(https://ree-jp.net)
 */

namespace ree_jp\coral_reef\form;

use Exception;
use Frago9876543210\EasyForms\elements\Button;
use Frago9876543210\EasyForms\elements\Input;
use Frago9876543210\EasyForms\elements\Label;
use Frago9876543210\EasyForms\elements\Toggle;
use Frago9876543210\EasyForms\forms\CustomForm;
use Frago9876543210\EasyForms\forms\CustomFormResponse;
use Frago9876543210\EasyForms\forms\MenuForm;
use pocketmine\Player;
use pocketmine\Server;
use ree_jp\coral_reef\account\AccountManager;
use ree_jp\coral_reef\sql\SettingConst;
use ree_jp\coral_reef\sql\SQLManager;

class SettingForm
{
    static function settingForm(): MenuForm
    {
        return new MenuForm('Menu -> Setting', '変更したい設定を選択してください',
            [new Button('座標の表示'), new Button('ニックネーム')],
            function (Player $p, Button $button): void {
                $xuid = $p->getXuid();
                switch ($button->getValue()) {
                    case 0:
                        $p->sendForm(self::boolForm($xuid, '座標を表示するか隠すのを変更出来ます', '隠す / 表示',
                            SettingConst::SHOW_COORDINATES, function () use ($p) {
                                AccountManager::updateShowCoordinates($p);
                            }));
                        break;
                    case 1:
                        $p->sendForm(self::inputForm($xuid, "ニックネームを設定できます\n無効にするにはニックネームを空白に設定してください", 'ニックネーム',
                            'せいちのかみ', SettingConst::NICK_NAME, function () use ($p) {
                                AccountManager::updateNickName($p);
                            }));
                        break;
                    default:
                        $p->sendMessage('エラーが発生しました');
                }
            });
    }

    static function boolForm(string $xuid, string $label, string $toggleMessage, string $settingType, ?callable $func = null): CustomForm
    {
        $isToggle = false;
        try {
            if (SQLManager::$manager->getSetting($xuid, $settingType) === 'true') $isToggle = true;
        } catch (Exception $e) {
            Server::getInstance()->getLogger()->critical("[SettingGet $settingType]" . $e->getMessage());
        }
        return new CustomForm('Setting -> ' . $settingType, [new Label($label), new Toggle($toggleMessage, $isToggle)],
            function (Player $p, CustomFormResponse $response) use ($settingType, $func): void {
                $toggle = $response->getToggle();
                if (!$toggle->hasChanged()) return;
                $result = $toggle->getValue() ? 'true' : 'false';
                try {
                    SQLManager::$manager->setSetting($p->getXuid(), $settingType, $result);
                    $p->sendMessage('設定を保存しました');
                    if (!is_null($func)) $func();
                } catch (Exception $e) {
                    $p->sendMessage('エラーが発生しました');
                    Server::getInstance()->getLogger()->critical("[SettingSave $settingType]" . $e->getMessage());
                }
            });
    }

    static function inputForm(string $xuid, string $label, string $inputMessage, string $holder, string $settingType, ?callable $func = null): CustomForm
    {
        $default = "";
        try {
            $result = SQLManager::$manager->getSetting($xuid, $settingType);
            if (!is_null($result)) $default = $result;
        } catch (Exception $e) {
            Server::getInstance()->getLogger()->critical("[SettingGet $settingType]" . $e->getMessage());
        }
        return new CustomForm('Setting -> ' . $settingType, [new Label($label), new Input($inputMessage, $holder, $default)],
            function (Player $p, CustomFormResponse $response) use ($settingType, $func): void {
                $result = $response->getInput()->getValue();
                if (empty($result)) $result = null;
                try {
                    SQLManager::$manager->setSetting($p->getXuid(), $settingType, $result);
                    $p->sendMessage('設定を保存しました');
                    if (!is_null($func)) $func();
                } catch (Exception $e) {
                    $p->sendMessage('エラーが発生しました');
                    Server::getInstance()->getLogger()->critical("[SettingSave $settingType]" . $e->getMessage());
                }
            });
    }
}
