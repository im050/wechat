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
use Im050\WeChat\Message\Formatter\Image;
use Im050\WeChat\Message\Formatter\Message;
use Im050\WeChat\Message\Formatter\Text;
use Im050\WeChat\Message\Formatter\Video;
use Im050\WeChat\Message\Formatter\Voice;

class MessageHandler
{

    protected static $_instance = null;

    protected $events = [];

    private function __construct()
    {
    }

    public static function getInstance()
    {
        if (self::$_instance === null) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function listen()
    {
        Console::log("开始监听消息...");

        $api = app()->api;

        $time = 0;

        while (true) {

            list($retcode, $selector) = $api->syncCheck();

            if ($retcode == 1100 || $retcode == 1101) {
                Console::log("微信已经退出或在其他地方登录", Console::ERROR);
            }

            if (time() - $time > 60) {
                app()->api->sendMessage('filehelper', '心跳 ' . Utils::now());
                $time = time();
            }

            switch ($selector) {
                case 0:
                    sleep(1);
                    break;
                case 2:
                case 3:
                case 4:
                case 6:
                case 7:
                    //拉取新消息
                    try {
                        $message = $api->pullMessage();
                    } catch (\Exception $e) {
                        Console::log("同步获取消息失败...", Console::WARNING);
                        continue;
                    }
                    if (!checkBaseResponse($message)) {
                        Console::log("接收数据异常，程序结束", Console::ERROR);
                    }
                    $this->handleMessage($message);
                    break;
                default:
                    Console::log("未知数据类型, selector:" . $selector);
            }
        }
    }

    public function handleMessage($message)
    {

        if ($message['AddMsgCount'] < 0) {
            return false;
        }

        $msg_list = $message['AddMsgList'];
        foreach ($msg_list as $key => $msg) {
            $msg_type = $msg['MsgType'];
            try {
                $message = MessageFactory::create($msg_type, $msg);
                //控制台打印消息
                $this->printMessage($message);
                if (isset($this->events['message'])) {
                    $this->events['message']['closure']($message, $this->events['message']['robot']);
                    //释放资源
                    unset($message);
                }
                if (config('debug')) {
                    $log = [
                        '消息类型' => $msg_type,
                        '消息数据' => Utils::json_encode($msg),
                        '日志时间' => Utils::now()
                    ];
                    $path = config('tmp_path') . '/log/message.log';
                    Logger::write($log, $path);
                }
            } catch (\Exception $e) {
                if (config('debug')) {
                    $log = [
                        '消息类型' => $msg_type,
                        '消息数据' => Utils::json_encode($msg),
                        '日志时间' => Utils::now()
                    ];
                    $path = config('tmp_path') . '/log/error_message.log';
                    Logger::write($log, $path);
                }
                Console::log("收到未知消息格式的数据类型，[MSG_TYPE] : {$msg_type}", Console::DEBUG);
            }
        }
        return true;
    }

    public function printMessage(Message $message)
    {
        $from_user = $message->getMessenger();
        $to_user = $message->getReceiver();

        if ($from_user) {
            $from_user_name = $from_user->getRemarkName();
        } else {
            $from_user_name = $message->getFromUserName();
        }
        if ($to_user) {
            $to_user_name = $to_user->getRemarkName();
        } else {
            $to_user_name = $message->getToUserName();
        }

        if ($message instanceof Text) {
            Console::log("$from_user_name 对 $to_user_name 说 ：" . $message->string());
        } else if ($message instanceof Image) {
            Console::log("$from_user_name 对 $to_user_name 发送了一张图片");
        } else if ($message instanceof Voice) {
            Console::log("$from_user_name 对 $to_user_name 发送了一段语音");
        } else if ($message instanceof Video) {
            Console::log("$from_user_name 对 $to_user_name 发送了一段视频");
        }

    }

    public function onMessage(\Closure $closure, $robot)
    {
        $this->events['message']['closure'] = $closure;
        $this->events['message']['robot'] = $robot;
    }

    public static function parseMessageEntity($content)
    {
        return preg_replace('/\<br(\s*)?\/?\>/i', PHP_EOL, html_entity_decode($content));
    }

}