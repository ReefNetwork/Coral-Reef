<?php
/*
 *  CCCCC                        lll RRRRRR                 fff
 * CC    C  oooo  rr rr    aa aa lll RR   RR   eee    eee  ff
 * CC      oo  oo rrr  r  aa aaa lll RRRRRR  ee   e ee   e ffff
 * CC    C oo  oo rr     aa  aaa lll RR  RR  eeeee  eeeee  ff
 *  CCCCC   oooo  rr      aaa aa lll RR   RR  eeeee  eeeee ff
 *
 * Copyright (c) 2022-2021. Ree-jp(https://ree-jp.net)
 */

namespace ree_jp\coral_reef\command;

use pocketmine\command\CommandSender;
use pocketmine\command\PluginCommand;
use pocketmine\Player;
use pocketmine\plugin\Plugin;
use ree_jp\coral_reef\form\FormManager;
use ree_jp\coral_reef\form\GatyaForm;

class ReefCommand extends PluginCommand
{
    public function __construct(Plugin $owner)
    {
        parent::__construct('reef', $owner);
        $this->setUsage('Reef Command');
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args)
    {
        if (!$sender instanceof Player) {
            $sender->sendMessage('このコマンドはプレイヤー専用です');
            return;
        }
        if (isset($args[0])) {
            switch ($args[0]) {
                case 'skill':
                    FormManager::skillSelectForm($sender);
                    return;
                case 'gatya':
                    GatyaForm::sendGatyaForm($sender);
                    return;
                case 'test':
                    $sender->sendMessage('super test message yeah');
                    break;
            }
        }
        $sender->sendMessage('そのコマンドは間違っています');
    }
}
