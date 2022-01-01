<?php

namespace ree_jp\coral_reef\command;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\plugin\Plugin;
use pocketmine\plugin\PluginOwned;
use ree_jp\coral_reef\account\AccountStore;
use ree_jp\coral_reef\form\ReefAdminForm;
use ree_jp\coral_reef\land\LandStore;
use ree_jp\coral_reef\sql\SQLManager;

class ReefAdminCommand extends Command implements PluginOwned
{

    public function __construct(private Plugin $owner, private SQLManager $repo, private AccountStore $accountStore, private LandStore $landStore)
    {
        parent::__construct("reef-admin");
        $this->setUsage("for admin");
        $this->setPermission("coral_reef.command.reef_admin");
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args)
    {
        if (!$sender instanceof Player) {
            $sender->sendMessage('このコマンドはプレイヤー専用です');
            return;
        }
        ReefAdminForm::sendForm($this->repo, $this->accountStore, $this->landStore, $sender);
    }

    public function getOwningPlugin(): Plugin
    {
        return $this->owner;
    }
}
