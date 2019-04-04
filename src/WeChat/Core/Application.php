<?php
namespace Im050\WeChat\Core;


/**
 * Class Application
 *
 * @package Im050\WeChat\Core
 * @property \Im050\WeChat\Core\Api $api
 * @property \Im050\WeChat\Message\MessageHandler $message
 * @property \Im050\WeChat\Task\TaskQueue $taskQueue
 * @property \Im050\WeChat\Core\Auth $auth
 * @property \Im050\WeChat\Component\Config $config
 * @property \Im050\WeChat\Component\HttpClient $http
 */
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
     * register a closure, base on addMap function.
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
     * make a instance
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
     * get an exists instance
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
     * register a singleton instance
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
     * check the instance closure exists
     *
     * @param $alias
     * @return bool
     */
    public function checkExists($alias)
    {
        return isset(self::$_map[$alias]);
    }

    /**
     * has the instance
     *
     * @param $alias
     * @return bool
     */
    public function hasInstance($alias)
    {
        return isset(self::$_instance[$alias]);
    }

    /**
     * add a callback
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
     * magic function
     * use app->var to visit the existed instance.
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