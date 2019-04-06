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

    /**
     * 心跳检测进程
     *
     * @var Process|null
     */
    protected $heartProcess = null;

    /**
     * 存放回调事件
     *
     * @var array
     */
    protected $events = [];

    private $listenMessageFailedTimes = 0;

    private $maxFailedTimes = 10;

    /**
     * 监听消息
     */
    public function listen()
    {
        Console::log("开始监听消息...");
        //执行登录成功回调
        app()->loginSuccessObserver->trigger();
        $this->heartCheck();
        while (true && $this->listenMessageFailedTimes < $this->maxFailedTimes) {
            try {
                if (!$this->checkRetCode()) {
                    continue;
                }
                // 拉取最新消息
                $message = app()->api->pullMessage();
                if (!checkBaseResponse($message)) {
                    Console::log("接收数据异常，程序结束", Console::ERROR);
                    break;
                }
            } catch (\Exception $e) {
                Console::log("同步获取消息失败，Exception: " . $e->getMessage(), Console::WARNING);
                continue;
            }

            $this->handleMessage($message);
        }
        //等待子进程回收
        Process::wait();
        exit(0);
    }

    /**
     * 同步检测
     *
     * @return bool
     */
    private function checkRetCode() : bool
    {
        try {
            list($retCode, $selector) = app()->api->syncCheck();
            if (in_array($retCode, array(1100, 1101, 1102, 1205))) {

                Console::log("微信已经退出或在其他地方登录", Console::ERROR);
            }
            if ($retCode != 0) {
                Console::log("微信客户端异常退出 {$retCode}", Console::ERROR);
            }
            app()->logoutObserver->trigger($retCode);
            if ($selector == 0) {
                return false;
            }
            $this->listenMessageFailedTimes > 0 && $this->listenMessageFailedTimes--;
            return true;
        } catch (\Exception $e) {
            $this->listenMessageFailedTimes++;
            return false;
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
                $this->friendlyMessage($message);
                app()->messageObserver->trigger($message);
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
    public function friendlyMessage(Message $message)
    {
        $friendlyMessage = $message->friendlyMessage();
        Console::log($friendlyMessage);
    }

    /**
     * 心跳检测
     *
     * @param int $seconds
     */
    protected function heartCheck($seconds = 600)
    {
        $parentPid = posix_getpid();
        $this->heartProcess = new Process(function () use ($seconds, $parentPid) {
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
                sleep($seconds + mt_rand(10, 20));
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