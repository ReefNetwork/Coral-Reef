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

namespace ree_jp\coral_reef\gatya;

use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use poggit\libasynql\SqlError;
use ree_jp\coral_reef\CoralReefPlugin;
use ree_jp\coral_reef\sql\SQLConst;
use ree_jp\coral_reef\sql\SQLManager;

class GatyaManager
{
    static function normalGatya(Player $p, int $number = 1): void
    {
        $xuid = $p->getXuid();
        SQLManager::$manager->getLog($xuid, SQLConst::LOG_GATYA, function (array $rows) use ($number, $p, $xuid) {
            for ($i = 1; $i <= $number; $i++) {
                $firstRand = mt_rand(1, 1000);
                $isLimit = true;
                for ($i = 0; $i < 100; $i++) { // 99回のガチャ履歴を調べてReefRareを引いてなかったら確定
                    $resultLog = array_pop($rows);
                    if (is_null($resultLog) || ($resultLog['subtype'] === 'ReefRare')) {
                        $isLimit = false;
                        break;
                    }
                }

                switch (true) {
                    case ($firstRand <= 5) || $isLimit:// 0.5% or 天井
                        $rows[] = ['subtype' => 'ReefRare'];
                        SQLManager::$manager->addLog($xuid, SQLConst::LOG_GATYA, 'ReefRare', '', SQLConst::NOW_TIME);
                        $p->sendMessage('ガチャを引きました(レア度: ' . TextFormat::GREEN . 'REEFレア' . TextFormat::RESET . ')');
                        $broadMessage = $p->getDisplayName() . 'さんが' . TextFormat::GREEN . 'REEFレア' . TextFormat::RESET . 'を引きました';
                        Server::getInstance()->broadcastMessage($broadMessage);
                        CoralReefPlugin::$plugin->discordBot->sendChat($broadMessage);
                        break;

                    case $firstRand <= (5 + 25):// 2.5%
                        $rows[] = ['subtype' => 'UltimateRare'];
                        SQLManager::$manager->addLog($xuid, SQLConst::LOG_GATYA, 'UltimateRare', '', SQLConst::NOW_TIME);
                        $p->sendMessage('ガチャを引きました(レア度: ' . TextFormat::GOLD . 'ウルトラレア[2.5%]' . TextFormat::RESET . ')');
                        break;

                    case $firstRand <= (30 + 100):// 10%
                        $rows[] = ['subtype' => 'SuperRare'];
                        SQLManager::$manager->addLog($xuid, SQLConst::LOG_GATYA, 'SuperRare', '', SQLConst::NOW_TIME);
                        $p->sendMessage('ガチャを引きました(レア度: ' . TextFormat::BLUE . 'スーパーレア[10%]' . TextFormat::RESET . ')');
                        break;

                    case $firstRand <= (130 + 300):// 30%
                        $rows[] = ['subtype' => 'Rare'];
                        SQLManager::$manager->addLog($xuid, SQLConst::LOG_GATYA, 'Rare', '', SQLConst::NOW_TIME);
                        $p->sendMessage('ガチャを引きました(レア度: ' . TextFormat::AQUA . 'レア[30%]' . TextFormat::RESET . ')');
                        break;

                    default:// 残り
                        $rows[] = ['subtype' => 'Normal'];
                        SQLManager::$manager->addLog($xuid, SQLConst::LOG_GATYA, 'Normal', '', SQLConst::NOW_TIME);
                        $p->sendMessage('ガチャを引きました(レア度: ' . TextFormat::DARK_GRAY . 'ノーマル' . TextFormat::RESET . ')');
                        break;
                }
            }
        }, function (SqlError $error) use ($p) {
            $p->sendMessage('エラーが発生しました');
            Server::getInstance()->getLogger()->error('[Gatya] ' . $p->getName() . 'さんの処理中に' . $error->getErrorMessage());
        });
    }
}
