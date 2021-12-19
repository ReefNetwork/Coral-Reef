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

use bbo51dog\bboform\element\ClosureButton;
use bbo51dog\bboform\element\Input;
use bbo51dog\bboform\element\Label;
use bbo51dog\bboform\element\Toggle;
use bbo51dog\bboform\form\ClosureCustomForm;
use bbo51dog\bboform\form\SimpleForm;
use Closure;
use pocketmine\Player;
use pocketmine\Server;
use poggit\libasynql\SqlError;
use ree_jp\coral_reef\account\SettingManager;
use ree_jp\coral_reef\sql\SettingConst;
use ree_jp\coral_reef\sql\SQLConst;
use ree_jp\coral_reef\sql\SQLManager;

class SettingForm
{
    static function settingForm(): SimpleForm
    {
        return (new SimpleForm())
            ->setTitle("Menu -> Setting")
            ->setText("変更したい設定を選択してください")
            ->addElements(
                new ClosureButton(
                    "座標の表示",
                    null,
                    function (Player $p, ClosureButton $button) {
                        self::sendBoolForm($p, "座標を表示しますか?", "表示 / 隠す",
                            SettingConst::COORDINATES, function () use ($p) {
                                $p->sendMessage("設定を保存しました");
                                SettingManager::updateShowCoordinates($p);
                            });
                    }
                ),
                new ClosureButton(
                    "スニーク中にスキル発動",
                    null,
                    function (Player $p, ClosureButton $button) {
                        self::sendBoolForm($p, 'スニーク中はスキルを無効にしますか?', '無効にする / しない',
                            SettingConst::SNEAK_SKILL, function () use ($p) {
                                $p->sendMessage('設定を保存しました');
                                SettingManager::updateOption($p, SettingConst::SNEAK_SKILL);
                            });
                    }
                ),
                new ClosureButton(
                    "ヒントを表示する",
                    null,
                    function (Player $p, ClosureButton $button) {
                        self::sendBoolForm($p, 'ヒントを表示しますか?', '表示する / しない', SettingConst::HIDE_SERVER_TIP,
                            function () use ($p) {
                                $p->sendMessage('設定を保存しました');
                                SettingManager::updateOption($p, SettingConst::HIDE_SERVER_TIP);
                            });
                    }
                ),
                new ClosureButton(
                    "水を凍らせる",
                    null,
                    function (Player $p, ClosureButton $button) {
                        self::sendBoolForm($p, 'ブロックを掘った時に前が水だった場合、水を別のブロックに変化させますか?', '変化させる / させない',
                            SettingConst::NO_FREEZE_WATER,
                            function () use ($p) {
                                $p->sendMessage('設定を保存しました');
                                SettingManager::updateOption($p, SettingConst::NO_FREEZE_WATER);
                            });
                    }
                ),
                new ClosureButton(
                    "地面のブロックにスキルを発動させない",
                    null,
                    function (Player $p, ClosureButton $button) {
                        self::sendBoolForm($p, "地面のブロックを掘った場所が自分の真下を含めた周りの1ブロックではない時でも、スキルを発動させますか?\n発動させないを選んだ場合、勢いで地面を掘ってしまうことを防げます",
                            '発動させる / させない',
                            SettingConst::BREAK_UNDER_GROUND,
                            function () use ($p) {
                                $p->sendMessage('設定を保存しました');
                                SettingManager::updateOption($p, SettingConst::BREAK_UNDER_GROUND);
                            });
                    }
                ),
                new ClosureButton(
                    "クールタイム中にブロックを掘れなくする",
                    null,
                    function (Player $p, ClosureButton $button) {
                        self::sendBoolForm($p, "スキルのクールタイム中でもブロックを掘れるようにしますか?",
                            '掘れるようにしない / する',
                            SettingConst::ALLOW_COOL_TIME_DIG,
                            function () use ($p) {
                                $p->sendMessage('設定を保存しました');
                                SettingManager::updateOption($p, SettingConst::ALLOW_COOL_TIME_DIG);
                            });
                    }
                ),
                new ClosureButton(
                    "ニックネーム",
                    null,
                    function (Player $p, ClosureButton $button) {
                        self::sendInputForm($p, "ニックネームを設定できます\n無効にするにはニックネームを空白に設定してください", 'ニックネーム',
                            'せいちのかみ', SettingConst::NICK_NAME, 10, function () use ($p) {
                                $p->sendMessage('設定を保存しました');
                                SettingManager::updateNickName($p);
                            });
                    }
                ),
            );
    }

    static function sendBoolForm(Player $p, string $label, string $toggleMessage, string $settingType, ?Closure $func = null): void
    {
        $xuid = $p->getXuid();
        SQLManager::$manager->getValue($xuid, SQLConst::TYPE_SETTINGS, $settingType, function (array $rows)
        use ($toggleMessage, $label, $func, $settingType, $p) {
            $row = array_shift($rows);
            $defaultToggle = isset($row['value']) && $row['value'] === 'true';
            $toggle = new Toggle($toggleMessage, $defaultToggle);
            $p->sendForm((new ClosureCustomForm(
                function (Player $p, ClosureCustomForm $form) use ($func, $settingType, $toggle) {
                    $result = $toggle->getValue() ? 'true' : 'false';
                    SQLManager::$manager->setValue($p->getXuid(), SQLConst::TYPE_SETTINGS, $settingType, $result, $func,
                        function (SqlError $error) use ($p, $settingType) {
                            $p->sendMessage('エラーが発生しました');
                            Server::getInstance()->getLogger()->critical("[SettingSave $settingType]" . $error->getMessage());
                        });
                }
            ))
                ->setTitle('Setting -> ' . $settingType)
                ->addElements(
                    new Label($label),
                    $toggle,
                )
            );
        });
    }

    static function sendInputForm(Player $p, string $label, string $inputMessage, string $holder, string $settingType, int $limit, ?Closure $func = null): void
    {
        $xuid = $p->getXuid();
        SQLManager::$manager->getValue($xuid, SQLConst::TYPE_SETTINGS, $settingType, function (array $rows)
        use ($limit, $p, $func, $holder, $inputMessage, $label, $settingType) {
            $row = array_shift($rows);
            $default = $row['value'] ?? "";
            $input = new Input($inputMessage, $holder, $default);
            $p->sendForm((new ClosureCustomForm(
                function (Player $p, ClosureCustomForm $form) use ($limit, $func, $settingType, $input) {
                    $result = $input->getValue();
                    if (mb_strlen($result) > $limit) {
                        $p->sendMessage($limit . "文字以下にしてください");
                        return;
                    }
                    if (empty($result)) $result = null;
                    SQLManager::$manager->setValue($p->getXuid(), SQLConst::TYPE_SETTINGS, $settingType, $result, $func,
                        function (SqlError $error) use ($p, $settingType) {
                            $p->sendMessage('エラーが発生しました');
                            Server::getInstance()->getLogger()->critical("[SettingSave $settingType]" . $error->getMessage());
                        });
                }
            ))
                ->setTitle('Setting -> ' . $settingType)
                ->addElements(
                    new Label($label),
                    $input,
                )
            );
        });
    }
}
