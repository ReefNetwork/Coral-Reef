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

namespace ree_jp\coral_reef\sql;

use pocketmine\utils\Config;
use poggit\libasynql\DataConnector;
use poggit\libasynql\libasynql;
use poggit\libasynql\SqlError;
use ree_jp\coral_reef\CoralReefPlugin;
use ree_jp\coral_reef\sql\repo\Repository;
use RuntimeException;

class RepositoryPool
{
    /** @var Repository[] */
    private array $repositories = [];
    private DataConnector $connection;

    public function __construct(CoralReefPlugin $plugin, string $path)
    {
        $config = new Config($path . "sql.yml");
        try {
            $this->connection = libasynql::create(CoralReefPlugin::$plugin, $config->get('database'), [
                "mysql" => "mysql.sql",
            ]);
        } catch (SqlError $error) {
            $plugin->criticalError("SQLサーバーに接続中に" . $error->getErrorMessage());
        }
    }

    public function getConnection(): DataConnector
    {
        return $this->connection;
    }

    /**
     * @param string $identity
     * @return Repository
     */
    public function get(string $identity): Repository
    {
        if (isset($this->repositories[$identity])) return $this->repositories[$identity];
        throw new RuntimeException("$identity not found");
    }

    public function register(Repository $repository, string $identity): void
    {
        $this->repositories[$identity] = $repository;
    }

    public function close(): void
    {
        foreach ($this->repositories as $repo) {
            $repo->close();
        }
        $this->getConnection()->waitAll();
        $this->getConnection()->close();
    }
}