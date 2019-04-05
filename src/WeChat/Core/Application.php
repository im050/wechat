<?php
namespace Im050\WeChat\Core;
use Im050\WeChat\Collection\Members;
use Im050\WeChat\Collection\MessageCollection;
use Im050\WeChat\Component\HttpClient;
use Im050\WeChat\Component\Storage\Handler\FileHandler;
use Im050\WeChat\Component\Storage\Storage;
use Im050\WeChat\Message\MessageHandler;
use Im050\WeChat\Task\TaskQueue;


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
 * @property \Im050\WeChat\Collection\Members $members
 * @property \Im050\WeChat\Core\SyncKey $syncKey
 * @property \Im050\WeChat\Core\Account $account
 * @property \Im050\WeChat\Collection\MessageCollection $messageCollection
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

    public function bootstrap() {
        $this->singleton("http", function () {
            return new HttpClient();
        });

        // auth for wechat login
        $this->singleton("auth", function () {
            return new Auth();
        });

        // init wechat api operator
        $this->singleton('api', function () {
            return new Api();
        });

        // account
        $this->singleton('account', function() {
            return new Account();
        });

        // Sync Key
        $this->singleton('syncKey', function() {
            return new SyncKey();
        });

        // message handler
        $this->singleton('message', function () {
            return new MessageHandler();
        });

        // message collection
        $this->singleton('messageCollection', function() {
            return new MessageCollection(config('mc_items'));
        });

        // task queue
        $this->singleton('taskQueue', function () {
            return new TaskQueue([
                'max_process_num' => config('task_process_num')
            ]);
        });

        // member collection
        $this->singleton('members', function() {
            return new Members();
        });

        // keymap for manage auth info.
        $this->singleton('keymap', function () {
            $config = app()->config;
            $tmpPath = $config->get('tmp_path');
            return new Storage(new FileHandler([
                'file' => $tmpPath . DIRECTORY_SEPARATOR . 'keymap.json'
            ]));
        });
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