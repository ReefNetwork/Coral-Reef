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
use pocketmine\Server;
use pocketmine\utils\Config;
use poggit\libasynql\DataConnector;
use poggit\libasynql\libasynql;
use poggit\libasynql\result\SqlColumnInfo;
use poggit\libasynql\SqlError;
use ree_jp\coral_reef\account\UserAccount;
use ree_jp\coral_reef\CoralReefPlugin;
use ree_jp\coral_reef\land\LandData;

class SQLManager
{
    static ?SQLManager $manager = null;

    private DataConnector $db;

    private array $users = [];

    /**
     * @throws PDOException
     */
    public function __construct(string $path)
    {
        $config = new Config($path . 'sql.yml');
        Server::getInstance()->getLogger()->info('[SQL] サーバーに接続中...');
        $this->db = libasynql::create(CoralReefPlugin::$plugin, $config->get('database'), [
            "mysql" => "mysql.sql",
        ]);
        Server::getInstance()->getLogger()->info('[SQL] 準備しています');
        $this->createTable();
        $this->db->waitAll();
        Server::getInstance()->getLogger()->info('[SQL] complete');
    }

    public function close(): void
    {
        Server::getInstance()->getLogger()->info('[SQL] クエリの終了を待っています');
        $this->db->waitAll();
        $this->db->close();
        Server::getInstance()->getLogger()->info('[SQL] complete');
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

    public function loadUser(string $xuid): void
    {
        $this->db->executeSelect('coral_reef.user.get', ['xuid' => $xuid], function (array $rows, SqlColumnInfo $columns) {
            if (!(array_key_exists('xuid', $rows) && array_key_exists('name', $rows) && array_key_exists('experience', $rows) &&
                array_key_exists('skill', $rows))) throw new Exception('USER_SQLの返り値が不正です');
            $account = new UserAccount($rows['xuid'], $rows['name'], intval($rows['experience']), $rows['skill']);
            $this->users[$account->xuid] = $account;
        });
    }

    /**
     * @param string $xuid
     * @return UserAccount|null
     */
    public function getUser(string $xuid): ?UserAccount
    {
        if (array_key_exists($xuid, $this->users)) return $this->users[$xuid];
        return null;
    }

    /**
     * @param string $xuid
     * @param string $name
     * @param string $ip
     * @throws Exception
     */
    public function setUser(string $xuid, string $name, string $ip): void
    {
        $ips = $this->getIps($xuid);
        if (is_null($ips)) $ips = [];
        if (!in_array($ip, $ips)) array_push($ips, $ip);
        $this->db->executeInsert('coral_reef.user.set', ['xuid' => $xuid, 'name' => $name, 'ips' => implode(':', $ips)], null,
            function (SqlError $error) use ($name) {
                Server::getInstance()->getLogger()->error("[SQL] $name のデータ保存中に" . $error->getErrorMessage());
            });
    }

    /**
     * @param string $xuid
     * @param string $experience
     * @throws Exception
     */
    public function setXp(string $xuid, string $experience): void
    {
        $prepare = $this->pdo->prepare('UPDATE USER SET EXPERIENCE = :experience WHERE XUID = :xuid');
        $prepare->execute([':experience' => $experience, ':xuid' => $xuid]);
    }

    /**
     * @param string $xuid
     * @param string|null $skill
     * @throws Exception
     */
    public function setSkill(string $xuid, ?string $skill): void
    {
        $prepare = $this->pdo->prepare('UPDATE USER SET Skill = :skill WHERE XUID = :xuid');
        $prepare->execute([':skill' => $skill, ':xuid' => $xuid]);
    }

    /**
     * @param string $xuid
     * @return array|null
     * @throws Exception
     */
    public function getIps(string $xuid): ?array
    {
        $prepare = $this->pdo->prepare('SELECT IPS FROM USER WHERE XUID = :xuid');
        $prepare->execute([':xuid' => $xuid]);
        $result = $prepare->fetchColumn();
        if ($result === false) return null;
        if (!is_string($result)) throw new Exception('ipが見つかりませんでした');
        return explode(':', $result);
    }

    /**
     * @param string $xuid
     * @param string $type
     * @param string $subtype
     * @return string|null
     * @throws PDOException
     */
    public function getValue(string $xuid, string $type, string $subtype): ?string
    {
        $prepare = $this->pdo->prepare('SELECT VALUE FROM VIRTUAL_VALUES WHERE XUID = :xuid AND TYPE = :type AND SUBTYPE = :subtype');
        $prepare->execute([':xuid' => $xuid, ':type' => strtolower($type), ':subtype' => strtolower($subtype)]);
        $result = $prepare->fetchColumn();
        if ($result === false) return null;
        return $result;
    }

    /**
     * @param string $xuid
     * @param string $type
     * @param string $subtype
     * @param string|null $value
     * @throws PDOException
     */
    public function setValue(string $xuid, string $type, string $subtype, ?string $value): void
    {
        $prepare = $this->pdo->prepare(
            'INSERT INTO VIRTUAL_VALUES VALUES (:xuid ,:colum ,:type ,:value) ON DUPLICATE KEY UPDATE VALUE = :value');
        $prepare->execute([':xuid' => $xuid, ':type' => strtolower($type), ':subtype' => strtolower($subtype), ':value' => $value]);
    }

    /**
     * @param string $xuid
     * @return array
     * @throws Exception
     */
    public function getWarps(string $xuid): array
    {
        $prepare = $this->pdo->prepare('SELECT NAME ,LEVEL ,X ,Y ,Z FROM WARP WHERE XUID = :xuid');
        $prepare->execute([':xuid' => $xuid]);
        $result = $prepare->fetchAll();
        if ($result === false) return [];
        return $result;
    }

    /**
     * @param string $xuid
     * @param string $name
     * @param string $level
     * @param int $x
     * @param int $y
     * @param int $z
     * @throws Exception
     */
    public function addWarp(string $xuid, string $name, string $level, int $x, int $y, int $z): void
    {
        $prepare = $this->pdo->prepare(
            'INSERT INTO WARP VALUES (:xuid ,:name ,:level ,:x ,:y ,:z) ON DUPLICATE KEY UPDATE LEVEL = :level ,X = :x ,Y = :y ,Z = :z');
        $prepare->execute([':xuid' => $xuid, ':name' => $name, ':level' => $level, ':x' => $x, ':y' => $y, ':z' => $z]);
    }

    /**
     * @param string $xuid
     * @param string $name
     * @throws Exception
     */
    public function deleteWarp(string $xuid, string $name): void
    {
        $prepare = $this->pdo->prepare(
            'DELETE FROM WARP WHERE XUID = :xuid AND NAME = :name');
        $prepare->execute([':xuid' => $xuid, ':name' => $name]);
    }

    /**
     * @return array
     * @throws Exception
     */
    public function getAllProtectLand(): array
    {
        $prepare = $this->pdo->prepare('SELECT * FROM LAND');
        $prepare->execute();
        return $prepare->fetchAll();
    }

    /**
     * @param LandData $land
     * @throws Exception
     */
    public function addProtectLand(LandData $land): void
    {
        $prepare = $this->pdo->prepare('INSERT INTO LAND VALUES (:xuid, :name, :level, :mx, :sx, :mz, :sz)');
        $prepare->execute([':xuid' => $land->xuid, ':name' => $land->name, ':level' => $land->level, ':mx' => $land->aabb->maxX, ':sx' => $land->aabb->minX,
            ':mz' => $land->aabb->maxZ, ':sz' => $land->aabb->minZ]);
    }

    /**
     * @param LandData $land
     * @throws Exception
     */
    public function deleteProtectLand(LandData $land): void
    {
        $prepare = $this->pdo->prepare('DELETE FROM LAND WHERE XUID = :xuid AND NAME = :name');
        $prepare->execute([':xuid' => $land->xuid, ':name' => $land->name]);
    }

    /**
     * @param string $xuid
     * @throws Exception
     */
    public function createLogTable(string $xuid): void
    {
//        $prepare = $this->logPdo->prepare("CREATE TABLE IF NOT EXISTS `:xuid`
//            (TYPE VARCHAR(99) NOT NULL ,OTHER VARCHAR(99) ,VALUE VARCHAR(999) ,TIME DATETIME )");
//        $prepare->execute([':xuid' => $xuid]);
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
//        if ($time === 'now') $time = date("Y-m-d H:i:s");
//        /** @noinspection SqlResolve */
//        $prepare = $this->logPdo->prepare("INSERT INTO `:xuid` VALUES (:type ,:other ,:value ,:time)");
//        $prepare->execute([':xuid' => $xuid, ':type' => $type, ':other' => $otherType, ':value' => $value, ':time' => $time]);
    }

    private function createTable(): void
    {
        $this->db->executeGeneric('coral_reef.init.tables.user');
        $this->db->executeGeneric('coral_reef.init.tables.whitelist');
        $this->db->executeGeneric('coral_reef.init.tables.warp');
        $this->db->executeGeneric('coral_reef.init.tables.land');
        $this->db->executeGeneric('coral_reef.init.tables.virtual_value');
    }
}
