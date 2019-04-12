<?php
namespace Im050\WeChat\Core;

use Im050\WeChat\Component\Config;
use Im050\WeChat\Component\Console;
use Monolog\Logger;
use Swoole\Process;

class Robot
{

    /**
     * default config
     *
     * @var array
     */
    private $config = [
        'log'     => [
            'level'            => Logger::INFO, //日志级别
            'path'             => '', //常规日志路径
            'message_log_path' => '' //消息日志路径
        ],
        'robot'   => [
            'tmp_path'          => '', //临时文件目录
            'save_qrcode'       => true, //是否保存二维码
            'auto_download'     => true, //是否自动下载
            'daemonize'         => false, //守护进程
            'task_process_num'  => 1, //任务进程数
            'providers'         => [], //服务提供注册类
            'max_message_items' => 2048 //最大消息保留数
        ],
        'cookies' => [
            'file' => '' //cookie文件路径
        ],
        'http' => [
            'timeout' => 60,
            'connect_timeout' => 10,
            'cookies' => true,
            'headers' => [
                'User-Agent' => 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)',
                'Accept'     => 'application/json',
                'Accept-Encoding' => 'gzip'
            ],
            'allow_redirects' => false,
            'verify' => true,
        ]
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
        app()->singleton('config', function () use ($config) {
            return new Config($config);
        });

        if (!isset($config['robot']['tmp_path']) || empty($config['robot']['tmp_path'])) {
            Console::log("Please setting tmp path.", Console::ERROR);
            exit(0);
        }

        config('cookies.file', config('robot.tmp_path') . '/cookies.txt');
        config('log.path', config('robot.tmp_path') . '/log/');
        config('log.message_log_path', config('log.path'));
    }

    /**
     * Run
     */
    public function run()
    {
        if ((new LoginService())->start()) {
            if (config('robot.daemonize')) {
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

    public function cron($cronString, callable $callback)
    {
        app()->crontab->register($cronString, $callback);
    }

    /**
     * 接收消息观察者
     *
     * @param callable $callback
     */
    public function onMessage(callable $callback)
    {
        app()->messageObserver->setCallback($callback);
    }

    /**
     * 登录成功回调事件
     *
     * @param callable $callback
     * @return void
     */
    public function onLoginSuccess(callable $callback)
    {
        app()->loginSuccessObserver->setCallback($callback);
    }

    /**
     * 微信退出
     *
     * @param callable $callback
     */
    public function onLogout(callable $callback)
    {
        app()->logoutObserver->setCallback($callback);
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