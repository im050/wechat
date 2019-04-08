<?php
/**
 * Created by PhpStorm.
 * User: linyulin
 * Date: 17/4/17
 * Time: 下午3:32
 */

namespace Im050\WeChat\Message;

use Im050\WeChat\Exception\UnknownMessageException;
use Im050\WeChat\Message\Formatter\Emoticon;
use Im050\WeChat\Message\Formatter\GroupSys;
use Im050\WeChat\Message\Formatter\Image;
use Im050\WeChat\Message\Formatter\Message;
use Im050\WeChat\Message\Formatter\MicroVideo;
use Im050\WeChat\Message\Formatter\NewFriend;
use Im050\WeChat\Message\Formatter\Recalled;
use Im050\WeChat\Message\Formatter\RedPacket;
use Im050\WeChat\Message\Formatter\SysMessage;
use Im050\WeChat\Message\Formatter\Text;
use Im050\WeChat\Message\Formatter\Transfer;
use Im050\WeChat\Message\Formatter\Video;
use Im050\WeChat\Message\Formatter\Voice;

class MessageFactory
{

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

    public static $factory = [
        self::TEXT_MESSAGE       => Text::class,
        self::IMAGE_MESSAGE      => Image::class,
        self::VOICE_MESSAGE      => Voice::class,
        self::MICROVIDEO_MESSAGE => Video::class,
        self::VIDEO_MESSAGE      => Video::class,
        self::SYS_MESSAGE        => SysMessage::class,
        self::EMOTICON_MESSAGE   => Emoticon::class,
        self::MICROVIDEO_MESSAGE => MicroVideo::class,
        self::RECALLED_MESSAGE   => Recalled::class
    ];

    /**
     * @param $type
     * @param $msg
     * @return Message
     * @throws \Exception
     */
    public static function create($type, $msg) : Message
    {
        if ($type == self::SYS_MESSAGE) {
            if (str_contains($msg['Content'], '利是') || str_contains($msg['Content'], '红包')) {
                return new RedPacket($msg);
            } elseif (str_contains($msg['Content'], '添加') || str_contains($msg['Content'], '打招呼')) {
                // 添加好友
                return new NewFriend($msg);
            } elseif (str_contains($msg['Content'], '加入了群聊') || str_contains($msg['Content'], '移出了群聊') || str_contains($msg['Content'], '改群名为') || str_contains($msg['Content'], '移出群聊') || str_contains($msg['Content'], '邀请你') || str_contains($msg['Content'], '分享的二维码加入群聊')) {
                return new GroupSys($msg);
            }
        } elseif ($type == self::APP_MESSAGE) {
            if ($msg['FileName'] === '微信转账') {
                return new Transfer($msg);
            }
//            } elseif ($msg['FileName'] === '我发起了位置共享') {
//                return (new Location())->make($msg);
//            } elseif (str_contains($msg['Content'], '该类型暂不支持，请在手机上查看')) {
//                return;
//            } else {
//                return $this->vbot->shareFactory->make($msg);
//            }
        }
        if (isset(self::$factory[$type])) {
            return new self::$factory[$type]($msg);
        } else {
            throw new UnknownMessageException("Unknown message [{$type}]: " . $msg['Content']);
        }
    }
}