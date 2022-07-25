<?php

namespace ree_jp\coral_reef\command;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\plugin\Plugin;
use pocketmine\plugin\PluginOwned;
use ree_jp\coral_reef\form\command\ReefAdminForm;
use ree_jp\coral_reef\sql\RepositoryPool;
use ree_jp\coral_reef\StoreHouse;

class ReefAdminCommand extends Command implements PluginOwned
{

    public function __construct(private Plugin $owner, private RepositoryPool $pool, private StoreHouse $store)
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
        if (!$this->testPermission($sender)) return;
        ReefAdminForm::sendForm($this->pool, $this->store, $sender);
    }

    public function getOwningPlugin(): Plugin
    {
        return $this->owner;
    }
}
