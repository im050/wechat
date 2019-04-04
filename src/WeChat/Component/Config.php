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

    public function __construct()
    {
    }

    public function setConfig($config)
    {
        $this->config = $config;
        return $this;
    }

    public function get($param)
    {
        return $this->__get($param);
    }

    public function set($param, $value)
    {
        $this->__set($param, $value);
        return $this;
    }

    public function __set($param, $value)
    {
        $this->config[$param] = $value;
    }

    public function __get($param)
    {
        return isset($this->config[$param]) ? $this->config[$param] : null;
    }

    public function toJSON() {
        return Utils::json_encode($this->config);
    }

    public function __toString()
    {
        return $this->toJSON();
    }
}