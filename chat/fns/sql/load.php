<?php
require_once __DIR__ . '/Medoo.php';
use Medoo\Medoo;
class DB {
    private static $instance = null;

    public static function connect() {
        if (!self::$instance) {
            self::$instance = new Medoo(Registry::load('config')->database);
        }
        return self::$instance;
    }

    public static function reset() {
        self::$instance = null;
    }

    public static function closeConnection() {
        self::$instance = null;
    }

    private function __clone() {}
    private function __construct() {}
}
?>