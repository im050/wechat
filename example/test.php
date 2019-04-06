#!/usr/bin/env php
<?php
define('BASE_PATH', dirname(dirname(__FILE__)));

include(BASE_PATH . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php');

use Im050\WeChat\Component\Console;
use Im050\WeChat\Component\Utils;
use Im050\WeChat\Core\Robot;
use Im050\WeChat\Message\Formatter\Message;
use Im050\WeChat\Message\MessageFactory;

$robot = new Robot([
    'tmp_path'      => BASE_PATH . DIRECTORY_SEPARATOR . 'tmp',
    'debug'         => false,
    'api_debug'     => false,
    'save_qrcode'   => false,
    'auto_download' => false,
    'daemonize'     => false,
    'task_process_num' => 1,
]);

$shut = [];

$robot->onLoginSuccess(function () {

    $filehelper = members()->getSpecials()->getContactByUserName("filehelper");
    if ($filehelper) {
        $filehelper->sendMessage("登录成功 " . Utils::now());
    }

    $contacts = members()->getContacts();
    $males = $contacts->getMaleContacts();
    $females = $contacts->getFemaleContacts();
    Console::log("共有男性联系人: " . $males->count() . " 个， 女性联系人: " . $females->count() . " 个");

});

$robot->onLogout(function (Robot $robot) {
    Console::log("程序已经退出.");
});

$robot->onMessage(function (Message $message, Robot $robot) {
    $messenger = $message->getMessenger();
    if ($messenger == null) {
        Console::log("获取消息发送者失败");
        return ;
    }
    if ($message->isGroup()) {
        //群消息不处理
        return ;
    }
    /** @var \Im050\WeChat\Collection\Element\MemberElement $master */
    $master = $robot->getContacts()->getContactByRemarkName("主人");
    if ($master == null) {
        return ;
    }
    switch ($message->getMessageType()) {
        case MessageFactory::TEXT_MESSAGE:
//            TaskQueue::run(RobotReply::class, [
//                'username'     => $messenger->getUserName(),
//                'from_message' => $message->string(),
//                'userid'       => md5($messenger->getUserName())
//            ]);
            //消息转发
            $master->sendMessage($messenger->getNickName() . ":" . $message->string() );
            break;
        case MessageFactory::EMOTICON_MESSAGE:
        case MessageFactory::IMAGE_MESSAGE:
            $master->sendMessage($messenger->getNickName() . ": [表情]" );
            break;
        case MessageFactory::RECALLED_MESSAGE:
            $master->sendMessage($messenger->getNickName() . ": [撤回]" );
            break;
    }
});

//运行
$robot->run();