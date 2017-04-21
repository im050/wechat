<?php
namespace Im050\WeChat\Message\Formatter;

use Im050\WeChat\Message\MessageHandler;

class Message
{
    public $message = [];

    public $content;

    public $from_user_name;

    public $to_user_name;

    public $msg_id;

    public $create_time;

    public $string;

    public $msg_type;

    //文本消息
    const TEXT_MESSAGE = 1;
    //图片消息
    const IMAGE_MESSAGE = 3;
    //语音消息
    const VOICE_MESSAGE = 34;
    //视频消息
    const VOIDE_MESSAGE = 43;
    //验证消息
    const VERIFYMSG_MESSAGE = 37;
    //好友请求消息
    const FRIEND_MESSAGE = 38;
    //系统消息
    const SYSNOTICE_MESSAGE = 9999;
    //好友消息
    const POSSIBLEFRIEND_MSG = 40;
    //名片消息
    const SHARECARD_MESSAGE = 42;
    //动画消息
    const EMOTICON_MESSAGE = 47;
    //本地消息
    const LOCATION_MESSAGE = 48;
    //分享消息
    const APP_MESSAGE = 49;
    //VOIP MESSAGE
    const VOIPMSG_MESSAGE = 50;
    //VOIP NOTIFY
    const VOIPNOTIFY_MESSAGE = 52;
    const VOIPINVITE_MESSAGE = 53;
    //未读消息
    const STATUSNOTIFY_MESSAGE = 51;
    //小视频视频消息
    const MICROVIDEO_MESSAGE = 62;
    //消息撤回
    const RECALLED_MESSAGE = 10002;
    //进入群
    const SYS_MESSAGE = 10000;

    public function __construct($message)
    {
        $this->message = $message;
        $content = MessageHandler::parseMessageEntity($this->message['Content']);
        $this->content = $content;
        $this->from_user_name = $this->message['FromUserName'];
        $this->to_user_name = $this->message['ToUserName'];
        $this->create_time = $this->message['create_time'];
        $this->msg_type = $this->message['MsgType'];
        //判断是否群消息
        if (substr($this->getFromUserName(), 0, 2) == '@@') {
            $content = explode(':'.PHP_EOL, $this->content);
            $this->content = $content[1];
        }
        //其他信息交给handle处理
        $this->handleMessage();
    }

    /**
     * 处理具体消息
     */
    public function handleMessage()
    {
        //todo: deal with content convert to string.
    }

    /**
     * 获取消息中文本部分
     *
     * @return mixed
     */
    public function string()
    {
        return $this->string;
    }

    /**
     * 获得发送消息的username
     *
     * @return mixed
     */
    public function getFromUserName()
    {
        return $this->from_user_name;
    }

    /**
     * 获得接受消息的username
     *
     * @return mixed
     */
    public function getToUserName()
    {
        return $this->to_user_name;
    }

    /**
     * 判断是否群消息
     *
     * @return bool
     */
    public function isGroup()
    {
        return substr($this->from_user_name, 0, 2) == '@@';
    }

    public function getMessageID() {
        return $this->msg_id;
    }

    public function getMessageType() {
        return $this->msg_type;
    }

    public function __destruct() {
        $log = "TYPE: " .$this->getMessageType() . PHP_EOL;
        $log .= "JSON: " . json_encode($this->message) . PHP_EOL;
        $log .= "TIME: " . date("Y-m-d H:i:s", time()) . PHP_EOL;
        file_put_contents(
            app()->config->json_path,
            $log .PHP_EOL,
            FILE_APPEND | LOCK_EX
        );
    }

}