#!/usr/bin/env php
<?php
define('BASE_PATH', dirname(dirname(__FILE__)));

include(BASE_PATH . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php');

use Im050\WeChat\Component\Console;
use Im050\WeChat\Component\Utils;
use Im050\WeChat\Core\Account;
use Im050\WeChat\Core\Robot;
use Im050\WeChat\Message\Formatter\Message;
use Im050\WeChat\Message\Formatter\Text;
use Im050\WeChat\Task\TaskQueue;

$robot = new Robot([
    'tmp_path'      => BASE_PATH . DIRECTORY_SEPARATOR . 'tmp',
    'debug'         => true,
    'api_debug'     => false,
    'save_qrcode'   => false,
    'auto_download' => false,
    'daemonize'     => false
]);

$shut = [];

$robot->onLoginSuccess(function (Robot $robot) {
    $filehelper = members()->getSpecials()->getContactByUserName("filehelper");
    if ($filehelper) {
        if (file_exists(__DIR__ . '/pic/thanks_boss.gif')) {
            $filehelper->sendMessage("登录成功 " . Utils::now());
        }
    }
});

$robot->onLogout(function (Robot $robot) {
    Console::log("程序已经退出.");
});

$robot->onMessage(function (Message $message, Robot $robot) {

    $shut = &$GLOBALS['shut'];

    $targetUser = $message->getMessenger();

    if ($message->getFromUserName() == Account::username()) {
        if ($message->isGroup()) {
            $targetUser = $message->getReceiver();
        } else {
            return false;
        }
    }

    if (!$targetUser) {
        return false;
    }

    //简单命令处理
    if ($message->getFromUserName() == Account::username()) {
        if ($message->string() == "#闭嘴") {
            TaskQueue::run('SendMessage', [
                'username' => $targetUser->getUserName(),
                'content'  => '已经停止自动应答 [' . $targetUser->getRemarkName() . '] 的消息'
            ]);
            $shut[$targetUser->getUserName()] = true;
        } else if ($message->string() == "#说话") {
            TaskQueue::run('SendMessage', [
                'username' => $targetUser->getUserName(),
                'content'  => '机器人正在待命'
            ]);
            $shut[$targetUser->getUserName()] = false;
            return $shut;
        } else if ($message->string() == "#图片") {
            app()->api->sendImage($targetUser->getUserName(), __DIR__ . '/test.jpg');
        }
    }

    if (isset($shut[$targetUser->getUserName()]) && $shut[$targetUser->getUserName()] == true) {
        return $shut;
    }

    //只给体验群发消息
    $white_list = [
        '机器人体验群',
        '杨杰',
        '202',
        '皮皮鳝，往里钻',
        '这样才是老子最酷灬',
        '史春阳',
        '罗志晨',
        '张帆',
        '考拉先生。',
        'filehelper'
    ];

    if (!in_array($targetUser->getRemarkName(), $white_list)) {
        return false;
    }

    if ($message instanceof Text) {
        //图灵机器人自动回复
        TaskQueue::run('RobotReply', [
            'username'     => $targetUser->getUserName(),
            'from_message' => $message->string(),
            'userid'       => md5($targetUser->getUserName())
        ]);
    } else {
        switch ($message->getMessageType()) {
            case Message::VOICE_MESSAGE:
                $text = '不要发语音，老夫听不懂。';
                break;
            case Message::IMAGE_MESSAGE:
            case Message::EMOTICON_MESSAGE:
                $file = Utils::getRandomFileName(__DIR__ . '/pic');
                if ($file) {
                    return $targetUser->sendImage($file);
                }
                break;
            case Message::VIDEO_MESSAGE:
            case Message::MICROVIDEO_MESSAGE:
                $text = '你是不是又发黄黄的小电影！！';
                break;
            case Message::SYS_MESSAGE:
                if ($message->isRedPacket()) {
                    return $targetUser->sendEmoticon(__DIR__ . '/pic/thanks_boss.gif');
                } else {
                    $text = $message->string();
                }
                break;
            default:
                $text = '你发的是什么鬼东西，我看不懂耶';
                break;
        }
        $targetUser->sendMessage($text);
    }

    return true;
});

//运行
$robot->run();