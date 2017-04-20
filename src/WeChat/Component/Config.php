<?php
/**
 * Created by PhpStorm.
 * User: linyulin
 * Date: 17/4/20
 * Time: 上午9:32
 */

namespace Im050\WeChat\Component;


class Config
{

    public $config = array();

    protected static $_instance = null;

    private function __construct()
    {
    }

    public static function getInstance()
    {
        if (self::$_instance === null) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function setConfig($config) {
        $this->config = $config;
    }

    public function get($param) {
        return $this->__get($param);
    }

    public function set($param, $value) {
        $this->__set($param, $value);
    }

    public function __set($param, $value) {
        $this->config[$param] = $value;
    }

    public function __get($param) {
        return isset($this->config[$param]) ? $this->config[$param] : null;
    }
}