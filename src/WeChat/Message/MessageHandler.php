<?php
/**
 * Created by PhpStorm.
 * User: linyulin
 * Date: 17/4/17
 * Time: 上午9:16
 */

namespace Im050\WeChat\Message;

use Im050\WeChat\Collection\ContactPool;
use Im050\WeChat\Component\Console;
use Im050\WeChat\Message\Formatter\Message;
use Im050\WeChat\Message\Formatter\Text;

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
        $api = app()->api;
        while (true) {
            list($retcode, $selector) = $api->syncCheck();

            if ($retcode == 1100 || $retcode == 1101) {
                Console::log("微信已经退出或在其他地方登录", Console::ERROR);
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
                    $message = $api->pullMessage();
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
                    $this->events['message']($message);
                    //释放资源
                    unset($message);
                }
            } catch (\Exception $e) {
                $log = "TYPE: Unknown Message Type, code: {$msg_type}" . PHP_EOL;
                $log .= "JSON:" . json_encode($msg) . PHP_EOL;
                $log .= "TIME:" . date("Y-m-d H:i:s", time()) . PHP_EOL;
                file_put_contents(
                    app()->config->log_path,
                    $log .PHP_EOL,
                    FILE_APPEND | LOCK_EX
                );
                Console::log("收到未知消息格式的数据类型，[MSG_TYPE] : {$msg_type}", Console::DEBUG);
            }
        }
        return true;
    }

    public function printMessage(Message $message)
    {
        if (!($message instanceof Text)) {
            return;
        }
        $contact_pool = ContactPool::getInstance();
        $from_user = $contact_pool->getByUserName($message->getFromUserName());
        $to_user = $contact_pool->getByUserName($message->getToUserName());
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
        Console::log("$from_user_name 对 $to_user_name 说 ：" . $message->string());
    }

    public function onMessage(\Closure $closure)
    {
        $this->events['message'] = $closure;
    }

    public static function parseMessageEntity($content)
    {
        return preg_replace('/\<br(\s*)?\/?\>/i', PHP_EOL, html_entity_decode($content));
    }

}