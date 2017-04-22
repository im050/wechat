<?php

define('BASE_PATH', dirname(dirname(__FILE__)));

include(BASE_PATH . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php');

use Im050\WeChat\Component\Console;
use Im050\WeChat\Core\Account;
use Im050\WeChat\Core\Robot;
use Im050\WeChat\Message\Formatter\Message;
use Im050\WeChat\Message\Formatter\Text;
use Im050\WeChat\Task\TaskQueue;

$robot = new Robot([
    'tmp_path'    => BASE_PATH,
    'cookie_path' => BASE_PATH,
    'log_path'    => BASE_PATH . DIRECTORY_SEPARATOR . 'message_log.txt',
    'json_path'   => BASE_PATH . DIRECTORY_SEPARATOR . 'json.txt',
]);

$robot->onMessage(function (Message $message) use ($robot) {

    //不给自己回复消息
    if ($message->getFromUserName() == Account::username()) {
        return false;
    }

    //只给体验群发消息
    $white_list = [
        '机器人体验群',
        '杨杰',
        'happyday',
        '皮皮鳝，往里钻',
        '这样才是老子最酷灬',
        '史春阳',
        '罗志晨',
        '张帆'
    ];

    $member = $robot->getContact()->getByUserName($message->getFromUserName());

    if (!$member) {
        return false;
    }

    if (!in_array($member->getRemarkName(), $white_list)) {
        return false;
    }

    if ($message instanceof Text) {
        try {
            //图灵机器人自动回复
            TaskQueue::run('RobotReply', [
                'username'     => $message->getFromUserName(),
                'from_message' => $message->string(),
                'userid'       => md5($message->getFromUserName())
            ]);

            //普通发送消息
            /*
            TaskQueue::run('SendMessage', [
                'username' => $message->getFromUserName(),
                'content' => '消息主体'
            ]);
            */
        } catch (Exception $e) {
            Console::log("发送消息失败");
        }
    }
//    } else {
//        switch($message->getMessageType()) {
//            case Message::VOICE_MESSAGE:
//                $type_name = '语音';
//                break;
//            case Message::IMAGE_MESSAGE:
//                $type_name = '图片';
//                break;
//            case Message::ANIMATE_MESSAGE:
//                $type_name = '动图';
//                break;
//            case Message::VIDEO_MESSAGE:
//                $type_name = '视频';
//                break;
//            default:
//                $type_name = '我不知道的东西';
//        }
//        try {
//            TaskQueue::run('SendMessage', [
//                'username' => $message->getFromUserName(),
//                'content'  => '我猜你发的是' . $type_name . '。'
//            ]);
//        } catch (Exception $e) {
//            Console::log("发送消息失败");
//        }
//    }

    return true;
});

$robot->run();