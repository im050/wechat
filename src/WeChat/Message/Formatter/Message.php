<?php
namespace Im050\WeChat\Message\Formatter;

use Im050\WeChat\Collection\Members;
use Im050\WeChat\Component\Utils;
use Im050\WeChat\Message\MessageHandler;
use Im050\WeChat\Task\TaskQueue;

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

    public $real_from_user_name;

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
    //群系统消息
    const SYS_MESSAGE = 10000;

    public function __construct($message)
    {
        $this->message = $message;
        $content = MessageHandler::parseMessageEntity($this->message['Content']);
        $this->content = $content;
        $this->from_user_name = $this->message['FromUserName'];
        $this->to_user_name = $this->message['ToUserName'];
        $this->create_time = $this->message['CreateTime'];
        $this->msg_type = $this->message['MsgType'];
        $this->msg_id = $this->message['MsgId'];
        //判断是否群消息
        if (substr($this->getFromUserName(), 0, 2) == '@@') {
            $content = explode(':'.PHP_EOL, $this->content);
            $this->content = $content[1];
            $this->real_from_user_name = $content[0];
        } else {
            $this->real_from_user_name = $this->from_user_name;
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

    public function getRealFromUserName() {
        return $this->real_from_user_name;
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

    public function getMessenger() {
        return Members::getInstance()->getContactByUserName($this->getFromUserName());
    }

    public function getReceiver() {
        return Members::getInstance()->getContactByUserName($this->getToUserName());
    }

    /**
     * 判断是否群消息
     *
     * @return bool
     */
    public function isGroup()
    {
        return Members::isGroup($this->from_user_name) || Members::isGroup($this->to_user_name);
    }

    public function getMessageID() {
        return $this->msg_id;
    }

    public function getMessageType() {
        return $this->msg_type;
    }

    /**
     * 下载消息资源
     *
     * @return bool
     */
    public function download() {
        if ($this instanceof Image) {
            $type = 'image';
        } else if ($this instanceof Voice) {
            $type = 'voice';
        } else {
            return false;
        }
        TaskQueue::run('Download', [
            'type' => $type,
            'msg_id' => $this->msg_id
        ]);
        return true;
    }

}