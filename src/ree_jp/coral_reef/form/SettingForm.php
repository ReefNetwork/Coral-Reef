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
use pocketmine\player\Player;
use pocketmine\Server;
use poggit\libasynql\SqlError;
use ree_jp\coral_reef\account\SettingManager;
use ree_jp\coral_reef\sql\SettingConst;
use ree_jp\coral_reef\sql\SQLConst;
use ree_jp\coral_reef\sql\SQLManager;

class SettingForm
{
    static function sendForm(SQLManager $repo, Player $p): void
    {
        $form = (new SimpleForm())
            ->setTitle("Setting")
            ->setText("変更したい設定を選択してください")
            ->addElements(
                new ClosureButton(
                    "座標の表示", null,
                    function (Player $p) use ($repo) {
                        self::sendBoolForm($repo, $p, "座標を表示しますか?", "表示 / 隠す",
                            SettingConst::COORDINATES, function () use ($repo, $p) {
                                $p->sendMessage("設定を保存しました");
                                SettingManager::updateShowCoordinates($repo, $p);
                            }
                        );
                    }
                ),
                new ClosureButton(
                    "スニーク中にスキル発動", null,
                    function (Player $p) use ($repo) {
                        self::sendBoolForm($repo, $p, 'スニーク中はスキルを無効にしますか?', '無効にする / しない',
                            SettingConst::SNEAK_SKILL, function () use ($repo, $p) {
                                $p->sendMessage('設定を保存しました');
                                SettingManager::updateOption($repo, $p, SettingConst::SNEAK_SKILL);
                            }
                        );
                    }
                ),
                new ClosureButton(
                    "ヒントを表示する", null,
                    function (Player $p) use ($repo) {
                        self::sendBoolForm($repo, $p, 'ヒントを表示しますか?', '表示する / しない', SettingConst::HIDE_SERVER_TIP,
                            function () use ($repo, $p) {
                                $p->sendMessage('設定を保存しました');
                                SettingManager::updateOption($repo, $p, SettingConst::HIDE_SERVER_TIP);
                            }
                        );
                    }
                ),
                new ClosureButton(
                    "水を凍らせる", null,
                    function (Player $p) use ($repo) {
                        self::sendBoolForm($repo, $p, 'ブロックを掘った時に前が水だった場合、水を別のブロックに変化させますか?', '変化させる / させない',
                            SettingConst::NO_FREEZE_WATER,
                            function () use ($repo, $p) {
                                $p->sendMessage('設定を保存しました');
                                SettingManager::updateOption($repo, $p, SettingConst::NO_FREEZE_WATER);
                            }
                        );
                    }
                ),
                new ClosureButton(
                    "地面のブロックにスキルを発動させない", null,
                    function (Player $p) use ($repo) {
                        self::sendBoolForm($repo, $p, "地面のブロックを掘った場所が自分の真下を含めた周りの1ブロックではない時でも、スキルを発動させますか?\n" .
                            "発動させないを選んだ場合、勢いで地面を掘ってしまうことを防げます",
                            '発動させる / させない',
                            SettingConst::BREAK_UNDER_GROUND,
                            function () use ($repo, $p) {
                                $p->sendMessage('設定を保存しました');
                                SettingManager::updateOption($repo, $p, SettingConst::BREAK_UNDER_GROUND);
                            }
                        );
                    }
                ),
                new ClosureButton(
                    "クールタイム中にブロックを掘れなくする", null,
                    function (Player $p) use ($repo) {
                        self::sendBoolForm($repo, $p, "スキルのクールタイム中でもブロックを掘れるようにしますか?",
                            '掘れるようにしない / する',
                            SettingConst::ALLOW_COOL_TIME_DIG,
                            function () use ($repo, $p) {
                                $p->sendMessage('設定を保存しました');
                                SettingManager::updateOption($repo, $p, SettingConst::ALLOW_COOL_TIME_DIG);
                            }
                        );
                    }
                ),
                new ClosureButton(
                    "ニックネーム", null,
                    function (Player $p) use ($repo) {
                        self::sendInputForm($repo, $p, "ニックネームを設定できます\n無効にするにはニックネームを空白に設定してください", 'ニックネーム',
                            'せいちのかみ', SettingConst::NICK_NAME, 10, function () use ($repo, $p) {
                                $p->sendMessage('設定を保存しました');
                                SettingManager::updateNickName($repo, $p);
                            }
                        );
                    }
                ),
            );
        $p->sendForm($form);
    }

    static function sendBoolForm(SQLManager $repo, Player $p, string $label, string $toggleMessage, string $settingType, ?Closure $func = null): void
    {
        $xuid = $p->getXuid();
        $repo->getValue($xuid, SQLConst::TYPE_SETTINGS, $settingType, function (array $rows)
        use ($repo, $toggleMessage, $label, $func, $settingType, $p) {
            $row = array_shift($rows);
            $defaultToggle = isset($row['value']) && $row['value'] === 'true';
            $toggle = new Toggle($toggleMessage, $defaultToggle);
            $p->sendForm((new ClosureCustomForm(
                function (Player $p) use ($repo, $func, $settingType, $toggle) {
                    $result = $toggle->getValue() ? 'true' : 'false';
                    $repo->setValue($p->getXuid(), SQLConst::TYPE_SETTINGS, $settingType, $result, $func,
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

    static function sendInputForm(SQLManager $repo, Player $p, string $label, string $inputMessage, string $holder, string $settingType,
                                  int        $limit, ?Closure $func = null): void
    {
        $xuid = $p->getXuid();
        $repo->getValue($xuid, SQLConst::TYPE_SETTINGS, $settingType, function (array $rows)
        use ($repo, $limit, $p, $func, $holder, $inputMessage, $label, $settingType) {
            $row = array_shift($rows);
            $default = $row['value'] ?? "";
            $input = new Input($inputMessage, $holder, $default);
            $p->sendForm((new ClosureCustomForm(
                function (Player $p) use ($repo, $limit, $func, $settingType, $input) {
                    $result = $input->getValue();
                    if (mb_strlen($result) > $limit) {
                        $p->sendMessage($limit . "文字以下にしてください");
                        return;
                    }
                    if (empty($result)) $result = null;
                    $repo->setValue($p->getXuid(), SQLConst::TYPE_SETTINGS, $settingType, $result, $func,
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
