<?php
namespace Im050\WeChat\Message\Formatter;

use Im050\WeChat\Component\Utils;
use Im050\WeChat\Core\FileSystem;
use Im050\WeChat\Message\MessageFactory;

class Recalled extends Message
{

    public $origin = 0;

    public $recall_message = null;

    /**
     * 解析message获取msgId
     *
     * @param $xml
     * @return string msgId
     */
    private function parseMsgId($xml)
    {
        preg_match('/<msgid>(\d+)<\/msgid>/', $xml, $matches);
        return $matches[1];
    }

    public function handleMessage()
    {
        $this->string = $this->getMessenger()->getRemarkName() . " 撤回了一条消息";
        $this->backup();
    }

    /**
     * 备份撤回数据
     *
     * @return bool
     */
    public function backup()
    {
        $this->origin = $this->parseMsgId($this->content);
        $this->recall_message = messages()->get($this->origin);
        try {
            $this->recall_message = MessageFactory::create($this->recall_message['MsgType'], $this->recall_message);
        } catch (\Exception $e) {
            return false;
        }
        //下载资源
        if (in_array(
            $this->recall_message->getMessageType(), array(
            Message::EMOTICON_MESSAGE,
            Message::IMAGE_MESSAGE,
            Message::VIDEO_MESSAGE,
            Message::MICROVIDEO_MESSAGE,
            Message::VOICE_MESSAGE
        ))) {
            return $this->recall_message->download(true);
        } else {
            $string = "[" . Utils::now() . "] ";
            $string .= $this->recall_message->printMessage();
            return FileSystem::append($string, FileSystem::getCurrentUserPath() . '/撤回消息记录.log');
        }
    }

    /**
     * 获取被撤回消息的源纪录
     *
     * @return null
     */
    public function getOriginMessage() {
        return $this->recall_message;
    }

    public function printMessage()
    {
        return $this->string;
    }
}