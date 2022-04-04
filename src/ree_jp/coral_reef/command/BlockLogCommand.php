<?php
/*
 *  CCCCC                        lll RRRRRR                 fff
 * CC    C  oooo  rr rr    aa aa lll RR   RR   eee    eee  ff
 * CC      oo  oo rrr  r  aa aaa lll RRRRRR  ee   e ee   e ffff
 * CC    C oo  oo rr     aa  aaa lll RR  RR  eeeee  eeeee  ff
 *  CCCCC   oooo  rr      aaa aa lll RR   RR  eeeee  eeeee ff
 *
 * Copyright (c) 2022. Ree-jp(https://ree-jp.net)
 */

namespace ree_jp\coral_reef\command;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\plugin\Plugin;
use pocketmine\plugin\PluginOwned;
use ree_jp\coral_reef\account\AccountStore;

//use pocketmine\utils\TextFormat;
//use pocketmine\world\Position;
//use ree_jp\coral_reef\form\PageViewForm;
//use ree_jp\mysql_logger\BlockLog;
//use ree_jp\mysql_logger\MysqlLoggerPlugin;
//use Throwable;

class BlockLogCommand extends Command implements PluginOwned
{
    public function __construct(private Plugin $owner, private AccountStore $store)
    {
        parent::__construct("block-log", "ブロックログを表示します", null, ["bl-log"]);
        $this->setPermission("coral_reef.command.menu");
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args)
    {
        if (!$this->testPermission($sender)) return;
        if (!$sender instanceof Player) {
            $sender->sendMessage("このコマンドはプレイヤー専用です");
            return;
        }
        $sender->sendMessage("§c >> ログ機能は現在使用できません");
//        if (count($args) < 3) {
//            $sender->sendMessage("コマンドが間違っています");
//            return;
//        }
//        $sender->sendMessage(TextFormat::DARK_GRAY . "データを確認しています...");
//        $pos = new Position(intval($args[0]), intval($args[1]), intval($args[2]), $sender->getWorld());
//
//        try {
//            MysqlLoggerPlugin::getLog($pos, function ($logs) use ($args, $sender): void {
//                $list = [];
//                foreach ($logs as $log) {
//                    if (!$log instanceof BlockLog) continue;
//                    $name = $this->store->getUserName($log->xuid);
//                    $list[] = "$name さんが$log->item §rで $log->block §rを$log->action しました ($log->time)";
//                }
//                PageViewForm::sendForm($sender, "BlockLog",
//                    "ブロックの破壊、設置ログです\n\nワールド名: {$sender->getWorld()->getFolderName()}\n座標: $args[0]:$args[1]:$args[2]", $list, 50);
//            }, null);
//        } catch (Throwable) {
//            $sender->sendMessage("ログを取得出来ませんでした");
//        }
    }

    public function getOwningPlugin(): Plugin
    {
        return $this->owner;
    }
}