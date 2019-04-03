<?php
namespace Im050\WeChat\Message\Formatter;

use Im050\WeChat\Collection\Element\Contact;
use Im050\WeChat\Collection\Element\Group;
use Im050\WeChat\Collection\Element\MemberElement;
use Im050\WeChat\Collection\Members;
use Im050\WeChat\Component\Console;
use Im050\WeChat\Component\Utils;
use Im050\WeChat\Task\TaskQueue;

class Message
{
    public $message = [];

    public $content;

    public $fromUserName;

    public $toUserName;

    public $msgId;

    public $createTime;

    public $string;

    public $msgType;

    public $realFromUserName;

    public $isGroupMessage = null;

    //文本消息
    const TEXT_MESSAGE = 1;
    //图片消息
    const IMAGE_MESSAGE = 3;
    //语音消息
    const VOICE_MESSAGE = 34;
    //视频消息
    const VIDEO_MESSAGE = 43;
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
        $content = Utils::formatContent($this->message['Content']);
        $this->content = $content;
        $this->fromUserName = $this->message['FromUserName'];
        $this->toUserName = $this->message['ToUserName'];
        $this->createTime = $this->message['CreateTime'];
        $this->msgType = $this->message['MsgType'];
        $this->msgId = $this->message['MsgId'];
        //处理具体发信人
        if (substr($this->getFromUserName(), 0, 2) == '@@') {
            $content = explode(':' . PHP_EOL, $this->content);
            $this->content = isset($content[1]) ? $content[1] : $this->content;
            $this->realFromUserName = isset($content[0]) && substr($content[0], 0, 1) == "@" ? $content[0] : $this->fromUserName;
        } else {
            $this->realFromUserName = $this->fromUserName;
        }
        //其他信息交给handle处理
        $this->handleMessage();
    }

    /**
     * 获取消息源数据
     *
     * @param string $field
     * @return array|mixed|null
     */
    public function raw($field = '')
    {
        if (empty($field)) {
            return $this->message;
        } else {
            return isset($this->message[$field]) ? $this->message[$field] : null;
        }
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
     * 获取实际发送用户的username
     *
     * @return mixed
     */
    public function getRealFromUserName()
    {
        return $this->realFromUserName;
    }

    /**
     * 获取群的username
     *
     * @return mixed
     */
    public function getGroupUserName()
    {
        if (!$this->isGroup()) {
            return false;
        }
        return (Members::isGroup($this->fromUserName)) ? $this->fromUserName : $this->toUserName;
    }

    /**
     * 获得当前发送消息的实际组成员
     *
     * @return bool|Contact
     */
    public function getGroupMessenger()
    {
        if (!$this->isGroup()) {
            return false;
        }
        return $this->getGroupMemberByUserName($this->getGroupUserName(), $this->getRealFromUserName());
    }

    public function getGroup()
    {
        if (!$this->isGroup()) {
            return false;
        }
        return members()->getGroups()->getContactByUserName($this->getGroupUserName());
    }

    /**
     * 获取组成员
     *
     * @param $groupUsername
     * @param $username
     * @return Contact
     */
    public function getGroupMemberByUserName($groupUsername, $username)
    {
        /** @var Group $group */
        $group = members()->getGroups()->getContactByUserName($groupUsername);
        if ($group) {
            $member = $group->getMemberByUserName($username);
        } else {
            $member = new Contact([
                'UserName'   => $username,
                'NickName'   => '未知群成员' . $username,
                'RemarkName' => '',
            ]);
        }
        return $member;
    }

    /**
     * 获得发送消息的username
     *
     * @return mixed
     */
    public function getFromUserName()
    {
        return $this->fromUserName;
    }

    /**
     * 获得接受消息的username
     *
     * @return mixed
     */
    public function getToUserName()
    {
        return $this->toUserName;
    }

    /**
     * 获取消息发送者
     *
     * @return MemberElement
     */
    public function getMessenger()
    {
        return members()->getContactByUserName($this->getFromUserName());
    }

    /**
     * 获取消息接收者
     *
     * @return MemberElement
     */
    public function getReceiver()
    {
        return members()->getContactByUserName($this->getToUserName());
    }

    /**
     * 判断是否群消息
     *
     * @return bool
     */
    public function isGroup()
    {
        if ($this->isGroupMessage === null) {
            $this->isGroupMessage = Members::isGroup($this->fromUserName) || Members::isGroup($this->toUserName);
        }
        return $this->isGroupMessage;
    }

    /**
     * 获取msg_id
     *
     * @return mixed
     */
    public function getMessageID()
    {
        return $this->msgId;
    }

    /**
     * 获取消息类型
     *
     * @return mixed
     */
    public function getMessageType()
    {
        return $this->msgType;
    }

    /**
     * 打印消息在控制台
     */
    public function printMessage()
    {
        $groupName = false;
        $receiver = $this->getReceiver()->getRemarkName();
        if ($this->getMessageType() == Message::SYS_MESSAGE) {
            $response = $this->string();
        } else {
            if ($this->isGroup()) {
                $groupName = $this->getGroup();
                $messenger = $this->getGroupMessenger()->getRemarkName();
            } else {
                $messenger = $this->getMessenger()->getRemarkName();
            }
            if ($groupName !== false) {
                $response = '[群][' . $groupName . '] ' . $messenger . " 发送了 [" . $this->string() . "]";
            } else {
                $response = $messenger . ' 对 ' . $receiver . ' 发送了 [' . $this->string() . ']';
            }
        }
        return $response;
    }

    /**
     * 下载消息资源
     *
     * @param bool $ignoreConfig 忽略自动下载的配置
     * @return bool
     */
    public function download($ignoreConfig = false)
    {
        if (!config('auto_download') && !$ignoreConfig) {
            return false;
        }

        if ($this instanceof Image) {
            $type = 'image';
        } else if ($this instanceof Voice) {
            $type = 'voice';
        } else if ($this instanceof Video || $this instanceof MicroVideo) {
            $type = 'video';
        } else if ($this instanceof Emoticon) {
            $type = 'emoticon';
        } else {
            return false;
        }
        Console::log("正在下载 [" . $type . "] 资源，MsgId：" . $this->msgId);
        // 执行下载任务
        TaskQueue::run('Download', [
            'type'   => $type,
            'msg_id' => $this->msgId
        ]);
        return true;
    }

}