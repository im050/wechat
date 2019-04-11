<?php
define('BASE_PATH', dirname(dirname(__FILE__)));

include(BASE_PATH . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php');

use Im050\WeChat\Collection\Element\Contact;
use Im050\WeChat\Collection\Element\Group;
use Im050\WeChat\Collection\Element\Official;
use Im050\WeChat\Component\Console;
use Im050\WeChat\Core\Account;
use Im050\WeChat\Message\Formatter\Message;
use Im050\WeChat\Message\Formatter\NewFriend;
use Im050\WeChat\Message\Formatter\Recalled;
use Im050\WeChat\Message\Formatter\RedPacket;
use Im050\WeChat\Message\Formatter\Text;
use Im050\WeChat\Message\Formatter\Transfer;

$robot = new \Im050\WeChat\Core\Robot([
    //临时文件夹
    'tmp_path'         => BASE_PATH . DIRECTORY_SEPARATOR . 'tmp',
    //日志级别
    'log_level'        => \Monolog\Logger::DEBUG,
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
 * 定时任务
 */
$robot->cron("*/1 * * * *", function () {
    $fileHelper = members()->getContactByUserName('filehelper');
    //第二个参数以阻塞方式发送消息
    $fileHelper->sendMessage("hello! " . time(), true);
});

$robot->onLogout(function ($code) {
    //code=1100 or 1101 在其他客户端登录
    //code=1102 无效的cookies
    //code=1105 api频率限制
    Console::log("退出登录了");
});

/**
 * 消息事件回调
 */
$robot->onMessage(function (Message $message) {
    //判断消息类型
    if ($message instanceof Text) {
        Console::log("文本消息");
    } elseif ($message instanceof RedPacket) {
        Console::log("是个红包消息");
    } elseif ($message instanceof NewFriend) {
        $message->approve(); //通过好友验证
    } elseif ($message instanceof Recalled) {
        $message->backup(); //备份撤回的消息
    } elseif ($message instanceof Transfer) {
        Console::log("转账消息，转账金额" . $message->getFee());
    } else {
        Console::log("其他资源消息，" . $message->getMessageType());
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
        Console::log("可能是条群消息");
    }

    //给发信人回复消息
    $file = '文件名';
    //文字消息
    //$messenger->sendMessage("文字消息");
    //动画表情消息
    //$messenger->sendEmoticon($file);
    //图片消息
    //$messenger->sendImage($file);
    //传输文件
    //$messenger->sendFile($file);

    //任务需要的参数
    $params = [
        'username'     => $messenger->getUserName(),
        'from_message' => $message->string(),
        'userid'       => md5($messenger->getUserName())
    ];
    //通过队列运行任务
    //app()->taskQueue->task(RobotReply::class, $params);
});

$robot->run();