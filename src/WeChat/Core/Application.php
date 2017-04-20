<?php
namespace Im050\WeChat\Core;


class Application
{

    private static $_map = [];

    private static $_instance = [];

    private static $_myself = null;

    private function __construct()
    {
        //nothing
    }

    public static function getInstance()
    {
        if (self::$_myself === null) {
            self::$_myself = new self();
        }
        return self::$_myself;
    }

    /**
     * 注册一个回调方法
     *
     * @param $alias
     * @param \Closure $closure
     * @return bool
     */
    public function register($alias, \Closure $closure)
    {
        $this->addMap($alias, $closure);
        return true;
    }

    /**
     * 根据别名生成对应对象
     *
     * @param $alias
     * @return mixed|null
     */
    public function make($alias)
    {
        if ($this->checkExists($alias)) {
            $instance = $this->getMap($alias);
            return $instance;
        } else {
            return null;
        }
    }

    /**
     * 获得已经存在的对象或生成对象
     *
     * @param $alias
     * @return bool|mixed
     */
    public function get($alias)
    {
        if ($this->checkExists($alias)) {
            if (!isset(self::$_instance[$alias])) {
                return $this->getMap($alias);
            } else {
                return self::$_instance[$alias];
            }
        } else {
            return false;
        }
    }

    /**
     * 注册一个单例对象
     *
     * @param $alias
     * @param \Closure $closure
     */
    public function singleton($alias, \Closure $closure)
    {
        $this->register($alias, $closure);
        self::$_instance[$alias] = $this->get($alias);
    }

    /**
     * 检查是否存在回调
     *
     * @param $alias
     * @return bool
     */
    public function checkExists($alias)
    {
        return isset(self::$_map[$alias]);
    }

    /**
     * 检查是否存在实例
     *
     * @param $alias
     * @return bool
     */
    public function hasInstance($alias)
    {
        return isset(self::$_instance[$alias]);
    }

    /**
     * 增加回调方法到map
     *
     * @param $alias
     * @param \Closure $closure
     */
    protected function addMap($alias, \Closure $closure)
    {
        self::$_map[$alias] = $closure($this);
    }

    /**
     * 得到回调方法
     *
     * @param $alias
     * @return mixed
     */
    protected function getMap($alias)
    {
        return self::$_map[$alias];
    }

    /**
     * 使得可以通过app()->alias访问对象
     *
     * @param $params
     * @return mixed|null
     */
    public function __get($params)
    {
        if (isset($this->$params)) {
            return $this->$params;
        } else {
            if (isset(self::$_instance[$params])) {
                return self::$_instance[$params];
            } else {
                return null;
            }
        }
    }

}