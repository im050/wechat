<?php
/**
 * Created by PhpStorm.
 * User: linyulin
 * Date: 17/4/17
 * Time: 下午3:32
 */

namespace Im050\WeChat\Message;

use Im050\WeChat\Message\Formatter\Animate;
use Im050\WeChat\Message\Formatter\Card;
use Im050\WeChat\Message\Formatter\DestoryMessage;
use Im050\WeChat\Message\Formatter\Emoticon;
use Im050\WeChat\Message\Formatter\Friend;
use Im050\WeChat\Message\Formatter\Image;
use Im050\WeChat\Message\Formatter\JoinGroup;
use Im050\WeChat\Message\Formatter\Message;
use Im050\WeChat\Message\Formatter\MicroVideo;
use Im050\WeChat\Message\Formatter\Recalled;
use Im050\WeChat\Message\Formatter\SysMessage;
use Im050\WeChat\Message\Formatter\Text;
use Im050\WeChat\Message\Formatter\UnRead;
use Im050\WeChat\Message\Formatter\Video;
use Im050\WeChat\Message\Formatter\Voice;
use Im050\WeChat\Message\Formatter\Share;

class MessageFactory
{

    public static $factory = [
        Message::TEXT_MESSAGE => Text::class,
        Message::IMAGE_MESSAGE => Image::class,
        Message::VOICE_MESSAGE => Voice::class,
        Message::MICROVIDEO_MESSAGE => Video::class,
        Message::VIDEO_MESSAGE => Video::class,
        Message::SYS_MESSAGE => SysMessage::class,
        Message::EMOTICON_MESSAGE => Emoticon::class,
        Message::MICROVIDEO_MESSAGE => MicroVideo::class,
        Message::RECALLED_MESSAGE => Recalled::class
    ];

    /**
     * @param $type
     * @param $msg
     * @return Message
     * @throws \Exception
     */
    public static function create($type, $msg)
    {
        if (isset(self::$factory[$type])) {
            return new self::$factory[$type]($msg);
        } else {
            throw new \Exception("不存在的消息格式");
        }
    }
}