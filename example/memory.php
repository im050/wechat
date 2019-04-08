<?php
define('BASE_PATH', dirname(dirname(__FILE__)));

include(BASE_PATH . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php');

use Im050\WeChat\Collection\Element\Contact;
use Im050\WeChat\Collection\Element\Group;
use Im050\WeChat\Collection\Element\MemberElement;
use Im050\WeChat\Component\Console;
use Im050\WeChat\Component\Utils;
use Im050\WeChat\Core\Account;
use Im050\WeChat\Message\Formatter\Message;
use Im050\WeChat\Message\MessageFactory;
use Im050\WeChat\Task\Job\RobotReply;
use Im050\WeChat\Task\TaskQueue;
use Im050\WeChat\Core\Robot;
use Swoole\Process;
use Im050\WeChat\Component\Timer;

$robot = new Robot([
    //临时文件夹
    'tmp_path'         => BASE_PATH . DIRECTORY_SEPARATOR . 'tmp',
    //debug日志
    'debug'            => true,
    //api接口数据debug日志
    'api_debug'        => false,
    //下载二维码
    'save_qrcode'      => false,
    //自动下载图片、语音、视频等资源
    'auto_download'    => false,
    //守护进程
    'daemonize'        => false,
    //任务处理进程数量
    'task_process_num' => 1,
]);

/**
 * 命令列表
 */
$commands = [
    '闭嘴' => 'shutUp',
    '召唤' => 'activateRobot'
];

/**
 * 静默列表
 */
$silence = [];

/**
 * 登录事件回调
 */
$robot->onLoginSuccess(function () {
    $fileHelper = members()->getContactByUserName('filehelper');
    //第二个参数以阻塞方式发送消息
    $fileHelper->sendMessage("机器人启动成功", true);
    Console::log("登录成功！");
    //获取男性，女性好友列表
    $contacts = members()->getContacts();
    $males = $contacts->getMaleContacts();
    $females = $contacts->getFemaleContacts();
    Console::log("共有男性联系人: " . $males->count() . " 个， 女性联系人: " . $females->count() . " 个");

    //初始化让所有用户处于静默状态
    members()->getContacts()->each(function (MemberElement $contact) {
        $silence = &$GLOBALS['silence'];
        $silence[$contact->getUserName()] = true;
    });
    members()->getGroups()->each(function (MemberElement $contact) {
        $silence = &$GLOBALS['silence'];
        $silence[$contact->getUserName()] = true;
    });

});

/**
 * 消息事件回调
 */
$robot->onMessage(function (Message $message) {
    $messageType = $message->getMessageType();
    $messenger = $message->getMessenger();
    Console::log("收到消息");
    if ($messenger == null) {
        return ;
    }
    Console::log("check messenger");
    if (!($messenger instanceof Group) && !($messenger instanceof Contact)) {
        return;
    }
    Console::log("check command");
    //检查是否有命令需要执行
    if (commandHandler($message)) {
        return;
    }
    Console::log("check silence");
    //静默状态检查
    if (silenceCheck($message)) {
        return;
    }

    Console::log("处理消息");
    switch ($messageType) {
        case MessageFactory::TEXT_MESSAGE:
            if ($message->isGroup() && !$message->isAt()) {
                return;
            }
            Console::log("机器人应答消息");
            TaskQueue::run(RobotReply::class, [
                'username'     => $messenger->getUserName(),
                'from_message' => $message->string(),
                'userid'       => md5($messenger->getUserName())
            ]);
            break;
        case MessageFactory::EMOTICON_MESSAGE:
        case MessageFactory::IMAGE_MESSAGE:
        Console::log("图片消息");
            $file = Utils::getRandomFileName(__DIR__ . '/pic');
            $messenger->sendEmoticon($file)->sendMessage("来，战个痛快！");
            break;
        case MessageFactory::SYS_MESSAGE:
            Console::log("系统消息");
            if ($message->isRedPacket()) {
                $file = __DIR__ . '/pic/thanks_boss.gif';
                $messenger->sendEmoticon($file);
            }
            break;
        case MessageFactory::RECALLED_MESSAGE:
            $messenger->sendMessage("撤回也没用，我看见了！");

    }
});

$robot->run();

/**
 * 处理命令
 *
 * @param Message $message
 * @return bool
 */
function commandHandler(Message $message)
{
    global $commands;
    if (substr($message->string(), 0, 1) == '#') {
        $command = explode("#", $message->string());
    }
    if (isset($command[1]) && function_exists($commands[$command[1]])) {
        $callback = $commands[$command[1]];
        unset($command[0], $command[1]);
        array_unshift($command, $message);
        call_user_func_array($callback, $command);
        return true;
    } else {
        return false;
    }
}

/**
 * 闭嘴命令
 *
 * @param Message $message
 */
function shutUp(Message $message)
{
    $silence = &$GLOBALS['silence'];
    if ($message->isGroup()) {
        $targetUser = $message->getGroup();
    } else {
        if ($message->getFromUserName() == Account::username()) {
            $targetUser = $message->getReceiver();
        } else {
            $targetUser = $message->getMessenger();
        }
    }
    $silence[$targetUser->getUserName()] = true;
    $targetUser->sendMessage("停止对[" . $targetUser->getRemarkName() . "]的自动应答");
}

/**
 * 激活命令
 *
 * @param Message $message
 */
function activateRobot(Message $message)
{
    $silence = &$GLOBALS['silence'];
    if ($message->isGroup()) {
        $targetUser = $message->getGroup();
    } else {
        if ($message->getFromUserName() == Account::username()) {
            $targetUser = $message->getReceiver();
        } else {
            $targetUser = $message->getMessenger();
        }
    }
    $silence[$targetUser->getUserName()] = false;
    $targetUser->sendMessage("召唤成功！");
}

function silenceCheck(Message $message)
{
    $silence = &$GLOBALS['silence'];
    if (isset($silence) && @$silence[$message->getFromUserName()]) {
        return true;
    }
    return false;
}