<?php
namespace Im050\WeChat\Core;

use Im050\WeChat\Collection\Members;
use Im050\WeChat\Collection\MessageCollection;
use Im050\WeChat\Component\Config;
use Im050\WeChat\Component\Console;
use Im050\WeChat\Component\HttpClient;
use Im050\WeChat\Component\Storage\Handler\FileHandler;
use Im050\WeChat\Component\Storage\Storage;
use Im050\WeChat\Message\MessageHandler;
use Im050\WeChat\Task\TaskQueue;
use Swoole\Process;

class Robot
{

    /**
     * default config
     *
     * @var array
     */
    private $config = [
        'tmp_path'         => '',
        'debug'            => false,
        'api_debug'        => false,
        'save_qrcode'      => true,
        'auto_download'    => true,
        'daemonize'        => false,
        'task_process_num' => 1
    ];

    public function __construct($config = array())
    {
        // merge config params
        $config = array_merge($this->config, $config);
        // check config and fix it.
        $this->initConfig($config);

        // mount something class into app container
        app()->bootstrap();

        // init http client
        app()->http->setConfig("cookiefile_path", app()->config->get('cookiefile_path'));
        app()->http->init();
    }

    /**
     * check and adjust config
     *
     * @param $config
     * @return void
     */
    private function initConfig($config)
    {
        // config manager
        app()->singleton('config', function () {
            return new Config();
        });

        if (!isset($config['tmp_path']) || empty($config['tmp_path'])) {
            Console::log("Please setting tmp path.", Console::ERROR);
        }

        if (!isset($config['cookie_path']) || empty($config['cookie_path'])) {
            $config['cookie_path'] = $config['tmp_path'];
        }

        app()->config->setConfig($config);
        app()->config->set('cookiefile_path', config('cookie_path') . '/cookies.txt')
            ->set('exception_log_path', config('tmp_path') . '/log/exception.log')
            ->set('warning_log_path', config('tmp_path') . '/log/warning.log')
            ->set('api_debug_log_path', config('tmp_path') . '/log/api_debug.log')
            ->set('message_log_path', config('tmp_path') . '/log/message.log')
            ->set('unknown_message_log_path', config('tmp_path') . '/log/unknown_message.log');
    }

    /**
     * Run
     */
    public function run()
    {
        if ((new LoginService())->start()) {
            if (config('daemonize')) {
                (new Process(function (Process $worker) {
                    $sid = posix_setsid();
                    if ($sid < 0)
                        $worker->exit(0);
                    app()->message->listen();
                    $worker->exit(0);
                }, true))->start();
                exit(0);
            } else {
                app()->message->listen();
            }
        }
    }

    /**
     * When you receive a message, you can do something right here by a closure.
     *
     * @param \Closure $closure
     */
    public function onMessage(\Closure $closure)
    {
        app()->message->onMessage($closure, $this);
    }

    /**
     * When you login success
     *
     * @param \Closure $closure
     */
    public function onLoginSuccess(\Closure $closure)
    {
        app()->message->onLoginSuccess($closure, $this);
    }

    /**
     * When you logout
     *
     * @param \Closure $closure
     */
    public function onLogout(\Closure $closure)
    {
        app()->message->onLogout($closure, $this);
    }

    /**
     * Quick to get contacts
     *
     * @return \Im050\WeChat\Collection\ContactCollection
     */
    public function getContacts()
    {
        return members()->getContacts();
    }

    /**
     * Quick to get groups
     *
     * @return \Im050\WeChat\Collection\ContactCollection
     */
    public function getGroups()
    {
        return members()->getGroups();
    }

    /**
     * Quick to get specials
     *
     * @return \Im050\WeChat\Collection\ContactCollection
     */
    public function getSpecials()
    {
        return members()->getSpecials();
    }

    /**
     * Quick to get officials
     *
     * @return \Im050\WeChat\Collection\ContactCollection
     */
    public function getOfficials()
    {
        return members()->getOfficials();
    }

}