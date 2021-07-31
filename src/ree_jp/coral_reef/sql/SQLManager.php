<?php


namespace ree_jp\coral_reef\sql;


use Exception;
use PDO;
use PDOException;
use ree_jp\coral_reef\account\UserAccount;

class SQLManager
{
    static SQLManager $manager;

    /**
     * USER :XUID:NAME:IPS:EXPERIMENT
     * BAN :TYPE:VALUE:REASON:TIME
     * WHITELIST :TYPE:VALUE:TIME
     * SETTING :XUID:TYPE:VALUE
     */
    private PDO $pdo;

    /**
     * XUID :TYPE:OTHER:VALUE:TIME
     */
    private PDO $logPdo;

    /**
     * @throws PDOException
     */
    public function __construct(string $dbName, string $host, string $user, string $pass)
    {
//        $rootPdo = new PDO("mysql:host=$host", $user, $pass,
//            [PDO::ATTR_CASE => PDO::CASE_UPPER, PDO::ATTR_TIMEOUT => 5]);
//        $prepareCreateDb = $rootPdo->prepare("CREATE DATABASE IF NOT EXISTS :dbName");
//        $prepareCreateDb->execute([':dbName' => $dbName]);
//        $prepareCreateDb->execute([':dbName' => $dbName . "Log"]);

        $options = [PDO::ATTR_CASE => PDO::CASE_UPPER,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 5];
        $dsn = "mysql:host=$host;dbname=$dbName;charset=utf8";
        $this->pdo = new PDO($dsn, $user, $pass, $options);
        $logDsn = "mysql:host=$host;dbname=${dbName}Log;charset=utf8";
        $this->logPdo = new PDO($logDsn, $user, $pass, $options);
        $this->createTable();
    }

    /**
     * Banされている時はその理由、されていないときはnull
     * @param string $xuid
     * @param string $ip
     * @return string|null
     * @throws Exception
     */
    public function getBanReason(string $xuid, string $ip): ?string
    {
        $collectQuery = $this->pdo->query("SELECT VALUE ,REASON ,TIME FROM BAN WHERE TYPE = 'ALL'");
        if ($collectQuery === false) return null;
        $collectPrepare = $this->pdo->prepare("SELECT NAME FROM USER WHERE XUID = :xuid AND FIND_IN_SET(:ip ,IPS)");
        while ($banData = $collectQuery->fetch(PDO::FETCH_ASSOC)) {
            if (!(array_key_exists('XUID', $banData) && array_key_exists('REASON', $banData))) throw new Exception('BAN_SQLの返り値が不正です');

            if ($xuid === $banData['XUID']) return $banData['REASON'];

            $collectPrepare->execute([':xuid' => $banData['XUID'], ':ip' => $ip]);
            $collectResult = $collectPrepare->fetchColumn();
            if ($collectResult !== false) return $banData['REASON'];
        }

        $singlePrepare = $this->pdo->prepare("SELECT REASON FROM BAN WHERE 
                            (TYPE = 'IP' AND VALUE = :ip ) OR 
                            (TYPE = 'XUID' AND VALUE = :xuid )");
        if ($singlePrepare === false) return null;
        $singlePrepare->execute([':ip' => $ip, ':xuid' => $xuid]);
        $prepareResult = $singlePrepare->fetchColumn();
        if (!is_string($prepareResult)) return null;
        return $prepareResult;
    }

    /**
     * @param string $xuid
     * @return UserAccount|null
     * @throws Exception
     */
    public function getUser(string $xuid): ?UserAccount
    {
        $prepare = $this->pdo->prepare("SELECT * FROM USER WHERE XUID = :xuid");
        if ($prepare === false) return null;
        $prepare->execute([':xuid' => $xuid]);
        $result = $prepare->fetch();
        if (!(array_key_exists('xuid', $result) || array_key_exists('name', $result) || array_key_exists('experiment', $result))) throw new Exception('USER_SQLの返り値が不正です');

        return new UserAccount($result['xuid'], $result['name'], $result['experiment']);
    }

    /**
     * @param string $xuid
     * @param string $type
     * @return string|null
     * @throws Exception
     */
    public function getSetting(string $xuid, string $type): ?string
    {
        $prepare = $this->pdo->prepare('SELECT VALUE FROM SETTING WHERE XUID = :xuid AND TYPE = :type');
        if ($prepare === false) throw new Exception('設定にアクセスできません');
        $prepare->execute([':xuid' => $xuid, ':type' => $type]);
        $result = $prepare->fetchColumn();
        if ($result === false) return null;
        return $result;
    }

    /**
     * @param string $xuid
     * @param string $type
     * @param string $value
     * @throws Exception
     */
    public function setSetting(string $xuid, string $type, string $value): void
    {
        $prepare = $this->pdo->prepare(
            'INSERT INTO SETTING (XUID, TYPE, VALUE) VALUES (:xuid,:type,:value) ON DUPLICATE KEY UPDATE VALUE = :value');
        if ($prepare === false) throw new Exception('設定にアクセスできません');
        $prepare->execute([':xuid' => $xuid, ':type' => $type, ':value' => $value]);
    }

    /**
     * @throws Exception
     */
    private function createLogTable(string $xuid): void
    {
        $prepare = $this->logPdo->prepare("CREATE TABLE IF NOT EXISTS :xuid(TYPE ENUM('join','quit','warp','skill','break','place','chat','other') NOT NULL ,OTHER VARCHAR,VALUE VARCHAR NOT NULL ,TIME DATETIME NOT NULL )");
        if ($prepare === false) throw new Exception('ログテーブルが作成できません');
        $prepare->execute([':xuid' => $xuid]);
    }

    private function createTable(): void
    {
        $this->pdo->exec('CREATE TABLE IF NOT EXISTS USER (XUID BIGINT UNSIGNED NOT NULL PRIMARY KEY ,NAME VARCHAR(100) NOT NULL ,IPS VARCHAR(9999) NOT NULL ,EXPERIMENT BIGINT UNSIGNED NOT NULL )');
        $this->pdo->exec("CREATE TABLE IF NOT EXISTS BAN (PRIMARY KEY (TYPE ,VALUE ),TYPE ENUM('ALL','XUID','IP') NOT NULL ,VALUE VARCHAR(20) NOT NULL ,REASON VARCHAR(999) NOT NULL ,TIME DATETIME )");
        $this->pdo->exec("CREATE TABLE IF NOT EXISTS WHITELIST (PRIMARY KEY (TYPE ,VALUE ),TYPE ENUM('XUID','IP') NOT NULL ,VALUE VARCHAR(20) NOT NULL ,REASON VARCHAR(999) NOT NULL ,TIME DATETIME )");
        $this->pdo->exec('CREATE TABLE IF NOT EXISTS SETTING (PRIMARY KEY (XUID ,TYPE),XUID BIGINT UNSIGNED NOT NULL ,TYPE VARCHAR(99) NOT NULL ,VALUE VARCHAR(99) NOT NULL )');
    }
}
