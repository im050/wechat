<?php
namespace Im050\WeChat\Core;

use Im050\WeChat\Collection\ConcatFactory;
use Im050\WeChat\Collection\ContactPool;
use Im050\WeChat\Component\Config;
use Im050\WeChat\Component\Console;
use Im050\WeChat\Message\MessageHandler;
use Im050\WeChat\Task\TaskQueue;

class Robot
{

    protected $app = null;

    protected $events = [];

    protected $config = [
        'cookie_path' => '',
        'log_path' => '',
    ];

    public function __construct($config = array())
    {

        $config = array_merge($this->config, $config);

        Config::getInstance()->setConfig($config);

        $this->app = Application::getInstance();
        //启动应用
        $this->boot();
        //配置应用
        $cookie_path = isset($config['cookie_path']) ? $config['cookie_path'] . DIRECTORY_SEPARATOR . 'cookies.txt' : __DIR__ . DIRECTORY_SEPARATOR . 'cookies.txt';
        //设置cookie路径
        http()->setConfig('cookiejar', $cookie_path);
        http()->setConfig('cookiefile', $cookie_path);
    }

    public function run()
    {

        Console::log("正在准备二维码...");

        $this->app->auth->openQRcode();

        Console::log("请扫描二维码");

        $flag = $this->app->auth->pollingLogin();

        if ($flag == Auth::LOGIN_TIMEOUT) {
            Console::log("扫码二维码超时，正在为您重新生成二维码...");
            return $this->run();
        }

        if ($flag != Auth::LOGIN_SUCCESS) {
            Console::log("程序运行异常，请重新启动", Console::ERROR);
        }

        Console::log("正在初始化账号数据...");

        $this->app->auth->webWxInit();

        Console::log("欢迎您，" . Account::nickname());

        if ($this->app->auth->statusNotify()) {
            Console::log("开启微信通知成功...");
        } else {
            Console::log("开启微信通知失败，可能导致其他问题...", Console::WARNING);
        }

        Console::log("正在加载联系人...");

        $this->initContact();

        Console::log("开始监听消息...");

        $this->app->message->listen();

        return true;
    }

    protected function initContact()
    {
        $contact_pool = ContactPool::getInstance();

        try {
            $data = app()->api->getContact();
        } catch (\Exception $e) {
            Console::log($e->getMessage(), Console::ERROR);
        }

        $member_list = $data['MemberList'];

        foreach ($member_list as $key => $item) {
            $contact_pool->add(ConcatFactory::create($item));
        }
    }

    public function boot()
    {
        //初始化微信登录权限类
        app()->singleton("auth", function () {
            return Auth::getInstance();
        });

        //初始化配置类
        app()->singleton("config", function(){
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
    }

    public function onMessage(\Closure $closure)
    {
        MessageHandler::getInstance()->onMessage($closure);
    }

    public function getConcat()
    {
        return ContactPool::getInstance();
    }

}