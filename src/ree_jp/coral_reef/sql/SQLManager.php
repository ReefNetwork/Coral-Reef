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

namespace ree_jp\coral_reef\sql;


use Exception;
use PDO;
use PDOException;
use ree_jp\coral_reef\account\UserAccount;

class SQLManager
{
    static SQLManager $manager;

    /**
     * USER :XUID:NAME:IPS:EXPERIENCE
     * BAN :TYPE:VALUE:REASON:TIME
     * WHITELIST :TYPE:VALUE:TIME
     * SETTING :XUID:TYPE:VALUE
     */
    private PDO $pdo;

    /**
     * XUID :TYPE:OTHER:VALUE:TIME
     */
    private PDO $logPdo;

    private array $users = [];

    /**
     * @throws PDOException
     */
    public function __construct(string $dbName, string $host, string $user, string $pass)
    {
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
        if (array_key_exists($xuid, $this->users)) return $this->users[$xuid];

        $prepare = $this->pdo->prepare('SELECT * FROM USER WHERE XUID = :xuid');
        if ($prepare === false) return throw new Exception('アカウントを取得する準備ができませんでした');
        $prepare->execute([':xuid' => $xuid]);
        $result = $prepare->fetch();
        if (!(array_key_exists('xuid', $result) || array_key_exists('name', $result) || array_key_exists('experience', $result))) throw new Exception('USER_SQLの返り値が不正です');

        $account = new UserAccount($result['xuid'], $result['name'], $result['experience']);
        $this->users[$xuid] = $account;
        return $account;
    }

    /**
     * @param string $xuid
     * @param string $experience
     * @throws Exception
     */
    public function setXp(string $xuid, string $experience): void
    {
        $prepare = $this->pdo->prepare('UPDATE USER SET EXPERIENCE = :experience WHERE XUID = :xuid');
        if ($prepare === false) throw new Exception('xpを保存する準備ができませんでした');
        if (!$prepare->execute([':experience' => $experience, ':xuid' => $xuid])) throw new Exception('xpを保存できませんでした');
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
            'INSERT INTO SETTING VALUES (:xuid ,:type ,:value) ON DUPLICATE KEY UPDATE VALUE = :value');
        if ($prepare === false) throw new Exception('設定を保存する準備ができませんでした');
        if (!$prepare->execute([':xuid' => $xuid, ':type' => $type, ':value' => $value])) throw new Exception('設定を保存できませんでした');
    }

    /**
     * @param string $xuid
     * @throws Exception
     */
    public function createLogTable(string $xuid): void
    {
        $prepare = $this->logPdo->prepare("CREATE TABLE IF NOT EXISTS :xuid (TYPE ENUM('join','quit','warp','skill','break','place','chat','other') NOT NULL ,OTHER VARCHAR(99) ,VALUE VARCHAR(999) ,TIME DATETIME )");
        if ($prepare === false) throw new Exception('ログ作成の準備ができませんでした');
        if (!$prepare->execute([':xuid' => $xuid])) throw new Exception('ログを作成できませんでした');
    }

    /**
     * @param string $xuid
     * @param string $type
     * @param string|null $time
     * @param string|null $otherType
     * @param string|null $value
     * @throws Exception
     */
    public function addLog(string $xuid, string $type, string $time = null, string $otherType = null, string $value = null): void
    {
        if ($time === 'now') $time = date("Y-m-d H:i:s");
        $prepare = $this->logPdo->prepare('INSERT INTO :xuid VALUES (:type ,:other ,:value ,:time)');
        if ($prepare === false) throw new Exception('ログの追加の準備ができませんでした');
        $prepare->execute([':type' => $type, ':other' => $otherType, ':value' => $value, ':time' => $time]);
    }

    private function createTable(): void
    {
        $this->pdo->exec('CREATE TABLE IF NOT EXISTS USER (XUID BIGINT UNSIGNED NOT NULL PRIMARY KEY ,NAME VARCHAR(100) NOT NULL ,IPS VARCHAR(9999) NOT NULL ,EXPERIENCE BIGINT UNSIGNED NOT NULL )');
        $this->pdo->exec("CREATE TABLE IF NOT EXISTS BAN (PRIMARY KEY (TYPE ,VALUE ),TYPE ENUM('ALL','XUID','IP') NOT NULL ,VALUE VARCHAR(20) NOT NULL ,REASON VARCHAR(999) NOT NULL ,TIME DATETIME )");
        $this->pdo->exec("CREATE TABLE IF NOT EXISTS WHITELIST (PRIMARY KEY (TYPE ,VALUE ),TYPE ENUM('XUID','IP') NOT NULL ,VALUE VARCHAR(20) NOT NULL ,REASON VARCHAR(999) NOT NULL ,TIME DATETIME )");
        $this->pdo->exec('CREATE TABLE IF NOT EXISTS SETTING (PRIMARY KEY (XUID ,TYPE),XUID BIGINT UNSIGNED NOT NULL ,TYPE VARCHAR(99) NOT NULL ,VALUE VARCHAR(99) NOT NULL )');
    }
}
