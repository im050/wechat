<?php
/**
 * Created by PhpStorm.
 * User: linyulin
 * Date: 17/4/17
 * Time: 上午9:16
 */

namespace Im050\WeChat\Message;

use Im050\WeChat\Component\Console;
use Im050\WeChat\Component\Logger;
use Im050\WeChat\Component\Utils;
use Im050\WeChat\Message\Formatter\Message;
use Im050\WeChat\Task\TaskQueue;
use Swoole\Process;

class MessageHandler
{

    protected static $_instance = null;

    /**
     * 心跳检测进程
     *
     * @var null
     */
    protected $heartProcess = null;

    /**
     * 存放回调事件
     *
     * @var array
     */
    protected $events = [];

    private function __construct()
    {
    }

    /**
     * 单例模式
     *
     * @return MessageHandler|null
     */
    public static function getInstance()
    {
        if (self::$_instance === null) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * 监听消息
     */
    public function listen()
    {
        Console::log("开始监听消息...");
        //执行登录成功回调
        if (isset($this->events['login_success'])) {
            $this->events['login_success']['closure']($this->events['login_success']['robot']);
        }
        $api = app()->api;
        $failedTimes = 0;
        $this->heartCheck();
        while (true) {
            try {
                list($retcode, $selector) = $api->syncCheck();
                if ($failedTimes > 0) {
                    $failedTimes--;
                }
            } catch (\Exception $e) {
                $failedTimes++;
                if ($failedTimes == 10) {
                    Console::log("监听消息失败超过 10 次，程序退出。", Console::ERROR);
                }
                Console::log("监听消息失败，Exception：" . $e->getMessage(), Console::WARNING);
                continue;
            }

            if ($retcode == 1100 || $retcode == 1101) {
                if (isset($this->events['logout']['closure'])) {
                    $this->events['logout']['closure']($this->events['logout']['robot']);
                }
                Console::log("微信已经退出或在其他地方登录", Console::ERROR);
            }

            if ($retcode != 0) {
                Console::log("微信客户端异常退出 {$retcode}", Console::ERROR);
            }

            if ($selector == 0) {
                sleep(1);
                continue;
            }

            try {
                $message = $api->pullMessage();
            } catch (\Exception $e) {
                Console::log("同步获取消息失败，Exception: " . $e->getMessage(), Console::WARNING);
                continue;
            }

            if (!checkBaseResponse($message)) {
                Console::log("接收数据异常，程序结束", Console::ERROR);
            }

            $this->handleMessage($message);
        }
    }

    /**
     * 处理消息
     *
     * @param $response
     * @return bool
     */
    public function handleMessage($response)
    {

        if (config('debug')) {
            $log = [
                '日志类型' => 'handleMessage',
                '日志数据' => Utils::json_encode($response),
                '记录时间' => Utils::now()
            ];
            Logger::write($log, config("message_log_path"));
        }

        if ($response['AddMsgCount'] < 0) {
            return false;
        }

        $messageList = $response['AddMsgList'];
        foreach ($messageList as $key => $msg) {
            $msgType = $msg['MsgType'];
            try {
                $message = MessageFactory::create($msgType, $msg);
                //将消息加入记录集合
                messages()->add($message);
                //控制台打印消息
                $this->printMessage($message);
                if (isset($this->events['message'])) {
                    $this->events['message']['closure']($message, $this->events['message']['robot']);
                    //释放资源
                    unset($message);
                }
                if (config('debug')) {
                    $log = [
                        '消息类型' => $msgType,
                        '消息数据' => Utils::json_encode($msg),
                        '日志时间' => Utils::now()
                    ];
                    $path = config('message_log_path');
                    Logger::write($log, $path);
                }
            } catch (\Exception $e) {
                if (config('debug')) {
                    $log = [
                        '消息类型' => $msgType,
                        '消息数据' => Utils::json_encode($msg),
                        '日志时间' => Utils::now()
                    ];
                    $path = config('unknown_message_log_path');
                    Logger::write($log, $path);
                }
                Console::log("收到未知消息格式的数据类型，[MSG_TYPE] : {$msgType}", Console::DEBUG);
            }
        }
        return true;
    }

    /**
     * 控制台打印消息内容
     *
     * @param Message $message
     */
    public function printMessage(Message $message)
    {
        $printMessage = $message->printMessage();
        Console::log($printMessage);
    }

    /**
     * 消息触发回调事件
     *
     * @param \Closure $closure
     * @param $robot
     */
    public function onMessage(\Closure $closure, $robot)
    {
        $this->events['message']['closure'] = $closure;
        $this->events['message']['robot'] = $robot;
    }

    /**
     * 登录成功回调事件
     *
     * @param \Closure $closure
     * @param $robot
     */
    public function onLoginSuccess(\Closure $closure, $robot)
    {
        $this->events['login_success']['closure'] = $closure;
        $this->events['login_success']['robot'] = $robot;
    }

    /**
     * 微信退出回调事件
     *
     * @param \Closure $closure
     * @param $robot
     */
    public function onLogout(\Closure $closure, $robot)
    {
        $this->events['logout']['closure'] = $closure;
        $this->events['logout']['robot'] = $robot;
    }

    /**
     * 心跳检测
     *
     * @param int $time
     */
    protected function heartCheck($time = 600)
    {
        $parentPid = posix_getpid();
        $this->heartProcess = new Process(function ($worker) use ($time, $parentPid) {
            while (true) {
                $time = time();
                $filehelper = members()->getSpecials()->getContactByUserName('filehelper');
                $ppid = posix_getppid();
                if ($ppid != $parentPid) {
                    $filehelper->sendMessage('你的父进程异常GG了，赶快去服务器上看一下吧。', true);
                    call_user_func(array($this, 'clear'));
                } else {
                    $filehelper->sendMessage("心跳正常\n内存使用情况：" . Utils::convert(memory_get_usage()) . "\n时间：" . Utils::now());
                }
                app()->keymap->set('login_time', $time)->save();
                sleep($time);
            }
        });
        $this->heartProcess->start();
    }

    /**
     * 清理进程
     */
    public function clear()
    {
        //关闭任务进程
        TaskQueue::shutdown();
        //关闭心跳进程
        $this->heartProcess->exit(0);
        //关闭自己
        exit(0);
    }

}