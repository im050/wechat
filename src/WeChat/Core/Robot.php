<?php
namespace Im050\WeChat\Core;

use Im050\WeChat\Collection\Members;
use Im050\WeChat\Component\Config;
use Im050\WeChat\Component\Console;
use Im050\WeChat\Component\Storage\Handler\FileHandler;
use Im050\WeChat\Component\Storage\Storage;
use Im050\WeChat\Message\MessageHandler;
use Im050\WeChat\Task\TaskQueue;

class Robot
{

    /**
     * 存放公共对象的容器
     *
     * @var Application|null
     */
    protected $app = null;

    /**
     * 默认配置参数
     *
     * @var array
     */
    protected $config = [
        'tmp_path'      => '',
        'debug'         => false,
        'api_debug'     => false,
        'save_qrcode'   => true,
        'auto_download' => true,
        'daemonize'     => false,
        'task_process_num' => 1
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
        $cookiePath = config('tmp_path') . '/cookies.txt';

        //设置cookie路径
        http()->setConfig('cookiejar', $cookiePath);
        http()->setConfig('cookiefile', $cookiePath);

        config('cookiefile_path', $cookiePath);
        config('exception_log_path', config('tmp_path') . '/log/exception.log');
        config('warning_log_path', config('tmp_path') . '/log/warning.log');
        config('api_debug_log_path', config('tmp_path') . '/log/api_debug.log');
        config('message_log_path', config('tmp_path') . '/log/message.log');
        config('unknown_message_log_path', config('tmp_path') . '/log/unknown_message.log');
    }

    /**
     * 调整Config
     *
     * @param $config
     * @return mixed
     */
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

    /**
     * 运行
     */
    public function run()
    {
        if ((new LoginService())->start()) {
            if (config('daemonize')) {
                (new \swoole_process(function ($worker) {
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
     * 启动加载
     */
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
        app()->singleton('taskQueue', function () {
            return new TaskQueue([
                'max_process_num' => config('task_process_num')
            ]);
        });

        //文件键值对管理
        app()->singleton('keymap', function () {
            $config = Config::getInstance();
            $tmpPath = $config->get('tmp_path');
            return new Storage(new FileHandler([
                'file' => $tmpPath . DIRECTORY_SEPARATOR . 'keymap.json'
            ]));
        });
    }

    /**
     * 消息回调
     *
     * @param \Closure $closure
     */
    public function onMessage(\Closure $closure)
    {
        MessageHandler::getInstance()->onMessage($closure, $this);
    }

    /**
     * 登录成功回调事件
     *
     * @param \Closure $closure
     */
    public function onLoginSuccess(\Closure $closure)
    {
        MessageHandler::getInstance()->onLoginSuccess($closure, $this);
    }

    /**
     * 退出登录回调事件
     *
     * @param \Closure $closure
     */
    public function onLogout(\Closure $closure)
    {
        MessageHandler::getInstance()->onLogout($closure, $this);
    }

    /**
     * 获取联系人列表
     *
     * @return \Im050\WeChat\Collection\ContactCollection
     */
    public function getContacts()
    {
        return Members::getInstance()->getContacts();
    }

    /**
     * 获取群组列表
     *
     * @return \Im050\WeChat\Collection\ContactCollection
     */
    public function getGroups()
    {
        return Members::getInstance()->getGroups();
    }

    /**
     * 获取特殊账号列表
     *
     * @return \Im050\WeChat\Collection\ContactCollection
     */
    public function getSpecials()
    {
        return Members::getInstance()->getSpecials();
    }

    /**
     * 获取公众号列表
     *
     * @return \Im050\WeChat\Collection\ContactCollection
     */
    public function getOfficials()
    {
        return Members::getInstance()->getOfficials();
    }

}