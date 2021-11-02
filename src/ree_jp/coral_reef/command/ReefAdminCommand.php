<?php

namespace ree_jp\coral_reef\command;

use pocketmine\command\CommandSender;
use pocketmine\command\PluginCommand;
use pocketmine\Player;
use pocketmine\plugin\Plugin;
use ree_jp\coral_reef\form\ReefAdminForm;

class ReefAdminCommand extends PluginCommand
{

    public function __construct(Plugin $owner)
    {
        parent::__construct("reef-admin", $owner);
        $this->setUsage("for admin");
        $this->setPermission("coral_reef.command.reef_admin");
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args)
    {
        if (!$sender instanceof Player) {
            $sender->sendMessage('このコマンドはプレイヤー専用です');
            return;
        }
        ReefAdminForm::sendReefAdminForm($sender);
    }
}