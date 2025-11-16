<?php

class Registry
{
    private static $instance;
    private $store;

    protected function __construct() {
        $this->store = array();
    }

    public static function __init() {
        if (self::$instance == null) {
            self::$instance = new self;
        }
        return self::$instance;
    }

    public static function add($key, $value) {
        $instance = self::__init();
        $instance->store[$key] = $value;
    }

    public static function load($key) {
        $instance = self::__init();
        if (!isset($instance->store[$key])) {
            throw new Exception("Key '{$key}' does not exist in the registry.");
        }
        return $instance->store[$key];
    }

    public static function stored($key) {
        $instance = self::__init();
        return isset($instance->store[$key]);
    }

    public static function remove($key) {
        $instance = self::__init();
        unset($instance->store[$key]);
    }

    public static function output() {
        $instance = self::__init();
        return get_object_vars($instance);
    }

    // Optional: Serialization methods, only if needed
    public function __sleep() {
        return array('store');
    }

    public function __wakeup() {
        $this->store = unserialize($this->store);
    }
}
