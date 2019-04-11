<?php
namespace Im050\WeChat\Message\Formatter;

use Im050\WeChat\Component\Utils;
use Im050\WeChat\Core\FileSystem;
use Im050\WeChat\Message\MessageFactory;

class Recalled extends Message
{

    private $origin = 0;  //原始消息ID

    private $recallMessage = null;

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
    }

    /**
     * 备份撤回数据
     *
     * @return bool
     */
    public function backup()
    {
        $this->origin = $this->parseMsgId($this->content);
        $this->recallMessage = messages()->get($this->origin);
        try {
            $this->recallMessage = MessageFactory::create($this->recallMessage['MsgType'], $this->recallMessage);
        } catch (\Exception $e) {
            return false;
        }
        //下载资源
        if (in_array(
            $this->recallMessage->getMessageType(), array(
            MessageFactory::EMOTICON_MESSAGE,
            MessageFactory::IMAGE_MESSAGE,
            MessageFactory::VIDEO_MESSAGE,
            MessageFactory::MICROVIDEO_MESSAGE,
            MessageFactory::VOICE_MESSAGE
        ))) {
            return $this->recallMessage->download(true);
        } else {
            $string = "[" . Utils::now() . "] ";
            $string .= $this->recallMessage->friendlyMessage();
            return FileSystem::append($string, FileSystem::getCurrentUserPath() . '/recalled.log');
        }
    }

    /**
     * @return int
     */
    public function getOrigin(): int
    {
        return $this->origin;
    }

    /**
     * @param int $origin
     * @return Recalled
     */
    public function setOrigin(int $origin): Recalled
    {
        $this->origin = $origin;
        return $this;
    }

    /**
     * @return null
     */
    public function getRecallMessage()
    {
        return $this->recallMessage;
    }

    /**
     * @param null $recallMessage
     * @return Recalled
     */
    public function setRecallMessage($recallMessage)
    {
        $this->recallMessage = $recallMessage;
        return $this;
    }

    /**
     * 获取被撤回消息的源纪录
     *
     * @return null
     */
    public function getOriginMessage() {
        return $this->recallMessage;
    }

    public function friendlyMessage()
    {
        return $this->string;
    }
}