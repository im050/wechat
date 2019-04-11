<?php
/**
 * Created by PhpStorm.
 * User: linyulin
 * Date: 17/4/17
 * Time: 上午9:16
 */

namespace Im050\WeChat\Message;

use Im050\WeChat\Component\Console;
use Im050\WeChat\Component\Utils;
use Im050\WeChat\Exception\AbnormalExitException;
use Im050\WeChat\Exception\SyncKeyException;
use Im050\WeChat\Exception\UnknownMessageException;
use Im050\WeChat\Message\Formatter\Message;
use Swoole\Process;

class MessageHandler
{

    /**
     * 心跳检测进程
     *
     * @var Process|null
     */
    public $heartProcess = null;

    private $listenMessageFailedTimes = 0;

    private $maxFailedTimes = 10;

    /**
     * 监听消息
     */
    public function listen(): void
    {
        Console::log("开始监听消息...");
        //执行登录成功回调
        app()->loginSuccessObserver->trigger();
        //启动心跳检测进程
        $this->heartbeat();
        //启动定时任务
        app()->crontab->start();
        //开始监听消息
        $this->pollingMessage();
        //等待子进程回收
        Process::wait();
        exit(0);
    }

    private function pollingMessage(): void
    {
        while (true && $this->listenMessageFailedTimes < $this->maxFailedTimes) {
            try {
                if (!$this->handleSyncCheck()) {
                    continue;
                }
                // 拉取最新消息
                $message = app()->api->pullMessage();
            } catch (SyncKeyException $e) {
                Console::log("同步获取消息失败，Exception: " . $e->getMessage(), Console::WARNING);
                $this->listenMessageFailedTimes++;
                sleep(1);
                continue;
            }
            $this->handleMessage($message);
        }
    }

    /**
     * 同步检测
     *
     * @return bool
     */
    private function handleSyncCheck(): bool
    {
        try {
            list($retCode, $selector) = app()->api->syncCheck();
            if (in_array($retCode, array(1100, 1101, 1102, 1205))) {
                Console::log("微信已经退出或在其他地方登录", Console::ERROR);
                app()->logoutObserver->trigger($retCode);
                return false;
            }
            if ($retCode != 0) {
                Console::log("微信客户端异常退出 {$retCode}", Console::ERROR);
                throw new AbnormalExitException("Client abnormal exit");
            }
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
    public function handleMessage($response): bool
    {
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
            } catch (UnknownMessageException $e) {
                Console::log($e->getMessage(), Console::DEBUG);
            } catch (\Exception $e) {
                Console::log($e->getMessage(), Console::DEBUG);
            } finally {
                app()->messageLog->debug($msg);
            }
        }
        return true;
    }

    /**
     * @param Message $message
     * @return void
     */
    public function friendlyMessage(Message $message): void
    {
        $friendlyMessage = $message->friendlyMessage();
        Console::log($friendlyMessage);
    }

    /**
     * 心跳检测
     *
     * @param int $seconds
     */
    private function heartbeat($seconds = 600): void
    {
        $parentPid = posix_getpid();
        $this->heartProcess = new Process(function () use ($seconds, $parentPid) {
            while (true) {
                $time = time();
                $filehelper = members()->getSpecials()->getContactByUserName('filehelper');
                $ppid = posix_getppid();
                if ($ppid != $parentPid) {
                    $filehelper->sendMessage('你的父进程异常GG了，赶快去服务器上看一下吧。', true);
                    call_user_func(array(app(), 'clear'));
                } else {
                    $filehelper->sendMessage("心跳正常\n内存使用情况：" . Utils::convert(memory_get_usage()) . "\n时间：" . Utils::now());
                }
                app()->keymap->set('login_time', $time)->save();
                sleep($seconds + mt_rand(10, 20));
            }
        });
        $this->heartProcess->start();
    }


}