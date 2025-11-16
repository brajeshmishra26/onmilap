<?php

include 'fns/sql/Medoo.php';
use Medoo\Medoo;

class DB {
    private static $instances = [];
    private static $lastCheck = [];

    public static function connect($workerId = null) {
        $cid = \Swoole\Coroutine::getCid();
        $workerId = $workerId ?? ($GLOBALS['swoole_worker_id'] ?? 0);
        $key = $workerId . '-' . $cid;
        $now = time();

        if (!isset(self::$instances[$key])) {

            Registry::load('config')->database['option'][PDO::ATTR_ERRMODE] = PDO::ERRMODE_EXCEPTION;
            Registry::load('config')->database['option'][PDO::ATTR_PERSISTENT] = false;

            self::$instances[$key] = new Medoo(Registry::load('config')->database);
            self::$lastCheck[$key] = $now;
        } elseif (($now - (self::$lastCheck[$key] ?? 0)) > 60) {
            try {
                $pdo = self::$instances[$key]->pdo;
                $pdo->query('SELECT 1');
            } catch (Exception $e) {
                error_log("DB reconnect triggered due to: " . $e->getMessage());
                self::$instances[$key] = new Medoo(Registry::load('config')->database);
            }
            self::$lastCheck[$key] = $now;
        }

        return self::$instances[$key];
    }

    public static function reset($workerId = null) {
        $cid = \Swoole\Coroutine::getCid();
        $workerId = $workerId ?? ($GLOBALS['swoole_worker_id'] ?? 0);
        $key = $workerId . '-' . $cid;

        if (isset(self::$instances[$key])) {
            unset(self::$instances[$key]);
            unset(self::$lastCheck[$key]);
        }
    }

    public static function closeConnection($workerId = null) {
        self::reset($workerId);
    }

    private function __clone() {}
    private function __construct() {}
}