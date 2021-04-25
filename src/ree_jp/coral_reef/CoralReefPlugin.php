<?php
namespace ree_jp\coral_reef;

use pocketmine\plugin\PluginBase;

class CoralReefPlugin extends PluginBase
{
    private const NOTICE = "§a>> ";

    public function onLoad()
    {
        $this->getLogger()->info(self::NOTICE."Load {$this->getName()}\nVer {$this->getDescription()->getVersion()}");
        parent::onLoad();
    }

    public function onEnable()
    {
        parent::onEnable();
    }
    
    public function onDisable()
    {
        parent::onDisable();
    }
}
