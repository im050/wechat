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
    /**
     * 存放配置数组
     *
     * @var array
     */
    public $config = [];

    /**
     * 合并初始化配置文件
     *
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        $this->config = array_merge($this->config, $config);
    }

    /**
     * 动态设置配置文件，支持.分隔数组
     *
     * @param $key
     * @param $value
     * @return Config
     */
    public function set($key, $value)
    {
        if (stripos($key, ".") !== false) {
            $temp = &$this->config;
            $path = explode(".", $key);
            foreach ($path as $node) {
                $temp = &$temp[$node];
            }
            $temp = $value;
        } else {
            $this->config[$key] = $value;
        }
        return $this;
    }

    /**
     * 获得配置信息，支持.分隔数组
     *
     * @param string $key
     * @param null $default
     * @return mixed|null
     */
    public function get($key = '', $default = null)
    {
        if (empty($key)) {
            return $this->config;
        }

        $temp = $this->config;

        if (stripos($key, ".") !== false) {
            $path = explode(".", $key);
            foreach ($path as $node) {
                if (isset($temp[$node])) {
                    $temp = $temp[$node];
                } else {
                    return $default;
                }
            }
        } else {
            $temp =  isset($this->config[$key]) ?  $this->config[$key] : $default;
        }

        return $temp;
    }

    public function config($param, $value = '', $force = false)
    {
        if (empty($value)) {
            return $this->get($param);
        } else {
            if (isset($this->config[$param])) {
                if ($force || empty($this->config[$param])) {
                    $this->set($param, $value);
                } else {
                    return false;
                }
            } else {
                $this->set($param, $value);
            }
            return true;
        }
    }

    public function __toString()
    {
        return Utils::json_encode($this->config);
    }
}