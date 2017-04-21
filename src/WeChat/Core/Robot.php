<?php
namespace Im050\WeChat\Core;

use Im050\WeChat\Collection\ContactPool;
use Im050\WeChat\Component\Config;
use Im050\WeChat\Component\Console;
use Im050\WeChat\Component\Storage\Handler\FileHandler;
use Im050\WeChat\Component\Storage\Storage;
use Im050\WeChat\Message\MessageHandler;
use Im050\WeChat\Task\TaskQueue;

class Robot
{

    protected $app = null;

    protected $events = [];

    protected $config = [
        'cookie_path' => '',
        'log_path'    => '',
        'tmp_path'    => '',
    ];

    public function __construct($config = array())
    {
        //合并配置参数
        $config = array_merge($this->config, $config);

        //检查配置参数
        $this->fixConfig($config);

        //将配置参数设置到Config类
        Config::getInstance()->setConfig($config);

        //初始化APP容器
        $this->app = Application::getInstance();

        //启动应用
        $this->boot();

        //配置应用
        $cookie_path = isset($config['cookie_path']) ? $config['cookie_path'] . DIRECTORY_SEPARATOR . 'cookies.txt' : __DIR__ . DIRECTORY_SEPARATOR . 'cookies.txt';

        //设置cookie路径
        http()->setConfig('cookiejar', $cookie_path);
        http()->setConfig('cookiefile', $cookie_path);
    }

    public function fixConfig($config)
    {
        if (!isset($config['tmp_path']) || empty($config['tmp_path'])) {
            Console::log("没有设置临时文件路径，请设置。", Console::ERROR);
        }

        if (!isset($config['cookie_path']) || empty($config['cookie_path'])) {
            $config['cookie_path'] = $config['tmp_path'];
        }

        return $config;
    }

    public function run()
    {
        if ((new LoginService())->start()) {
            app()->message->listen();
        }
    }

    public function boot()
    {
        //初始化微信登录权限类
        app()->singleton("auth", function () {
            return Auth::getInstance();
        });

        //初始化配置类
        app()->singleton("config", function () {
            return Config::getInstance();
        });

        //同步轮询
        app()->singleton('api', function () {
            return new Api();
        });

        //消息处理类
        app()->singleton('message', function () {
            return MessageHandler::getInstance();
        });

        //任务队列
        app()->singleton('task_queue', function () {
            return new TaskQueue();
        });

        //文件键值对管理
        app()->singleton('keymap', function () {
            $config = Config::getInstance();
            $tmp_path = $config->get('tmp_path');
            return new Storage(new FileHandler([
                'path' => $tmp_path . DIRECTORY_SEPARATOR . 'keymap.json'
            ]));
        });
    }

    public function onMessage(\Closure $closure)
    {
        MessageHandler::getInstance()->onMessage($closure);
    }

    public function getContact()
    {
        return ContactPool::getInstance();
    }

}