#!/usr/bin/env php
<?php
define('BASE_PATH', dirname(dirname(__FILE__)));

include(BASE_PATH . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php');

use Im050\WeChat\Core\Account;
use Im050\WeChat\Core\Robot;
use Im050\WeChat\Message\Formatter\Message;
use Im050\WeChat\Message\Formatter\Text;
use Im050\WeChat\Task\TaskQueue;

$robot = new Robot([
    'tmp_path'    => BASE_PATH . DIRECTORY_SEPARATOR . 'tmp',
    'debug'       => true,
    'save_qrcode' => true,
    'daemonize'   => false
]);

$shut = [];

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
            \Im050\WeChat\Component\Console::log("接收到指令");
            TaskQueue::run('SendMessage', [
                'username' => $targetUser->getUserName(),
                'content'  => '机器人正在待命'
            ]);
            $shut[$targetUser->getUserName()] = false;
            return $shut;
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
                $type_name = '语音';
                break;
            case Message::IMAGE_MESSAGE:
                $type_name = '图片';
                break;
            case Message::VIDEO_MESSAGE:
            case Message::MICROVIDEO_MESSAGE:
                $type_name = '视频';
                break;
            default:
                $type_name = '我不知道的东西';
        }
        $targetUser->sendMessage("我猜你发的是" . $type_name);
    }

    return true;
});

$robot->run();