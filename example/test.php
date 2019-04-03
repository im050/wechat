#!/usr/bin/env php
<?php
define('BASE_PATH', dirname(dirname(__FILE__)));

include(BASE_PATH . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php');

use Im050\WeChat\Component\Console;
use Im050\WeChat\Component\Utils;
use Im050\WeChat\Core\Robot;
use Im050\WeChat\Message\Formatter\Message;

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
//    $filehelper = members()->getSpecials()->getContactByUserName("filehelper");
//    $filehelper->sendMessage("当前历史记录:" . messages()->count());
    $messenger = $message->getMessenger();
    if ($messenger == null) {
        Console::log("获取消息发送者失败");
        return ;
    }
    echo "come on";
    switch ($message->getMessageType()) {
        case Message::TEXT_MESSAGE:
            $message->getMessenger()->sendMessage("ok");
            break;
        case Message::EMOTICON_MESSAGE:
        case Message::IMAGE_MESSAGE:
            $file = Utils::getRandomFileName(__DIR__ . '/pic');
            $messenger->sendEmoticon($file);
            break;
    }
});

//运行
$robot->run();