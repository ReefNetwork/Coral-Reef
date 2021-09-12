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

use Closure;
use Frago9876543210\EasyForms\elements\Button;
use Frago9876543210\EasyForms\elements\Input;
use Frago9876543210\EasyForms\elements\Label;
use Frago9876543210\EasyForms\elements\Toggle;
use Frago9876543210\EasyForms\forms\CustomForm;
use Frago9876543210\EasyForms\forms\CustomFormResponse;
use Frago9876543210\EasyForms\forms\MenuForm;
use pocketmine\Player;
use pocketmine\Server;
use poggit\libasynql\SqlError;
use ree_jp\coral_reef\account\SettingManager;
use ree_jp\coral_reef\sql\SettingConst;
use ree_jp\coral_reef\sql\SQLConst;
use ree_jp\coral_reef\sql\SQLManager;

class SettingForm
{
    static function settingForm(): MenuForm
    {
        return new MenuForm('Menu -> Setting', '変更したい設定を選択してください',
            [new Button('座標の表示'), new Button('スニーク中にスキル発動'), new Button('ヒントを表示する'), new Button('ニックネーム')],
            function (Player $p, Button $button): void {
                switch ($button->getValue()) {
                    case 0:
                        self::sendBoolForm($p, '座標を表示するしますか?', '表示 / 隠す',
                            SettingConst::COORDINATES, function () use ($p) {
                                $p->sendMessage('設定を保存しました');
                                SettingManager::updateShowCoordinates($p);
                            });
                        break;
                    case 1:
                        self::sendBoolForm($p, 'スニーク中はスキルを無効にしますか?', '無効にする / しない',
                            SettingConst::SNEAK_SKILL, function () use ($p) {
                                $p->sendMessage('設定を保存しました');
                                SettingManager::updateShowCoordinates($p);
                            });
                        break;
                    case 2:
                        self::sendBoolForm($p, 'ヒントを表示しますか?', '表示する / しない', SettingConst::HIDE_SERVER_TIP,
                            function () use ($p) {
                                $p->sendMessage('設定を保存しました');
                                SettingManager::updateServerTip($p);
                            });
                        break;
                    case 3:
                        self::sendInputForm($p, "ニックネームを設定できます\n無効にするにはニックネームを空白に設定してください", 'ニックネーム',
                            'せいちのかみ', SettingConst::NICK_NAME, function () use ($p) {
                                $p->sendMessage('設定を保存しました');
                                SettingManager::updateNickName($p);
                            });
                        break;
                    default:
                        $p->sendMessage('エラーが発生しました');
                }
            });
    }

    static function sendBoolForm(Player $p, string $label, string $toggleMessage, string $settingType, ?Closure $func = null): void
    {
        $xuid = $p->getXuid();
        SQLManager::$manager->getValue($xuid, SQLConst::TYPE_SETTINGS, $settingType, function (array $rows)
        use ($toggleMessage, $label, $func, $settingType, $p) {
            $row = array_shift($rows);
            $defaultToggle = false;
            $resultIsNull = false;
            if (isset($row['value']) && $row['value'] === 'true') $defaultToggle = true;
            $p->sendForm(new CustomForm('Setting -> ' . $settingType, [new Label($label), new Toggle($toggleMessage, $defaultToggle)],
                function (Player $p, CustomFormResponse $response) use ($settingType, $func, $resultIsNull): void {
                    $toggle = $response->getToggle();
                    if (!($toggle->hasChanged() || $resultIsNull)) return;
                    $result = $toggle->getValue() ? 'true' : 'false';
                    SQLManager::$manager->setValue($p->getXuid(), SQLConst::TYPE_SETTINGS, $settingType, $result, $func,
                        function (SqlError $error) use ($p, $settingType) {
                            $p->sendMessage('エラーが発生しました');
                            Server::getInstance()->getLogger()->critical("[SettingSave $settingType]" . $error->getMessage());
                        });
                }));
        });
    }

    static function sendInputForm(Player $p, string $label, string $inputMessage, string $holder, string $settingType, ?Closure $func = null): void
    {
        $xuid = $p->getXuid();
        SQLManager::$manager->getValue($xuid, SQLConst::TYPE_SETTINGS, $settingType, function (array $rows)
        use ($p, $func, $holder, $inputMessage, $label, $settingType) {
            $row = array_shift($rows);
            $default = "";
            if (isset($row['value'])) $default = $row['value'];
            $p->sendForm(new CustomForm('Setting -> ' . $settingType, [new Label($label), new Input($inputMessage, $holder, $default)],
                function (Player $p, CustomFormResponse $response) use ($settingType, $func): void {
                    $result = $response->getInput()->getValue();
                    if (empty($result)) $result = null;
                    SQLManager::$manager->setValue($p->getXuid(), SQLConst::TYPE_SETTINGS, $settingType, $result, $func,
                        function (SqlError $error) use ($p, $settingType) {
                            $p->sendMessage('エラーが発生しました');
                            Server::getInstance()->getLogger()->critical("[SettingSave $settingType]" . $error->getMessage());
                        });
                }));
        });
    }
}
