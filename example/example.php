<?php
define('BASE_PATH', dirname(dirname(__FILE__)));

include(BASE_PATH . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php');

use Im050\WeChat\Component\Console;
use Im050\WeChat\Message\Formatter\Message;
use Im050\WeChat\Collection\Element\Contact;
use Im050\WeChat\Collection\Element\Group;
use Im050\WeChat\Collection\Element\Official;
use Im050\WeChat\Core\Account;
use Im050\WeChat\Task\TaskQueue;

$robot = new \Im050\WeChat\Core\Robot([
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
    'task_process_num' => 10,
]);

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
});

/**
 * 消息事件回调
 */
$robot->onMessage(function(Message $message, $robot){

    //获取消息类型
    $messageType = $message->getMessageType();
    //判断消息类型
    switch($messageType) {
        case Message::TEXT_MESSAGE: //equals "$message instanceof Text";
            Console::log("文本消息");
            break;
        case Message::VOICE_MESSAGE:
            Console::log("语音消息");
            break;
        //and so on...
    }

    //获取信息发送者
    $messenger = $message->getMessenger();
    //判断消息接收者类型
    if ($messenger instanceof Contact) {
        Console::log("联系人发送的消息");
    } else if ($messenger instanceof Official) {
        Console::log("公众号推送的消息");
    } else if ($messenger instanceof Group) {
        Console::log("来自群消息");
    } else {
        Console::log("来自特殊账号消息");
    }

    //判断是否群消息, 这里isGroup不等价于 "$messenger instanceof Group"
    if ($message->isGroup()) {
        //获取消息具体的群
        $group = $message->getGroup();
        //具体的发信群成员
        $groupMessenger = $message->getGroupMessenger();
        Console::log("群：" . $group->getNickName() . ", 群成员：" . $groupMessenger->getNickName());
    }

    //消息的接收者
    $receiver = $message->getReceiver();

    if ($receiver->getUserName() == Account::username()) {
        Console::log("这是一条发给我的消息");
    } else {
        //可能是条群消息
    }

    //给发信人回复消息
    $file = '文件名';
    //文字消息
    $messenger->sendMessage("文字消息");
    //动画表情消息
    $messenger->sendEmoticon($file);
    //图片消息
    $messenger->sendImage($file);
    //传输文件
    $messenger->sendFile($file);

    //允许回复的列表
    $whiteContacts = [
        '机器人体验群',
        '皮皮鳝，往里钻',
        '这样才是老子最酷灬'
    ];

    if (!in_array($messenger->getRemarkName(), $whiteContacts)) {
        return ;
    }

    //任务队列
    $job = 'RobotReply'; //内置的图灵机器人回复任务
    //任务需要的参数
    $params = [
        'username'     => $messenger->getUserName(),
        'from_message' => $message->string(),
        'userid'       => md5($messenger->getUserName())
    ];
    TaskQueue::run($job, $params); //equals "app()->task_queue->task($job, $params)";
});