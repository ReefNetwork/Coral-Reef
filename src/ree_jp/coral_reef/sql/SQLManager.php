<?php


namespace ree_jp\coral_reef\sql;


use PDO;
use PDOException;
use ree_jp\coral_reef\account\UserAccount;

class SQLManager
{
    static SQLManager $manager;

    private PDO $pdo;
    private PDO $logPdo;

    /**
     * @throws PDOException
     */
    public function __construct(string $dbName, string $host, string $user, string $pass)
    {
        $rootPdo = new PDO("mysql:host=$host", $user, $pass,
            [PDO::ATTR_CASE => PDO::CASE_UPPER, PDO::ATTR_TIMEOUT => 5]);
        $prepareCreateDb = $rootPdo->prepare("CREATE DATABASE IF NOT EXISTS :dbName");
        $prepareCreateDb->execute([':dbName' => $dbName]);
        $prepareCreateDb->execute([':dbName' => $dbName . "Log"]);

        $dsn = "mysql:host=$host;dbname=$dbName;charset=utf8";
        $options = [PDO::ATTR_CASE => PDO::CASE_UPPER,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 5];
        $this->pdo = new PDO($dsn, $user, $pass, $options);
        $logDsn = "mysql:host=$host;dbname=${dbName}Log;charset=utf8";
        $this->logPdo = new PDO($logDsn, $user, $pass, $options);
    }

    /**
     * Banされている時はその理由、されていないときはnull
     * @param string $xuid
     * @return string|null
     */
    public function getBan(string $xuid): ?string
    {
    }

    /**
     * @param string $xuid
     * @return UserAccount|null
     */
    public function getUser(string $xuid): ?UserAccount
    {
    }

    private function createTable(): void
    {

    }
}
