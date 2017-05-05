<?php
namespace Im050\WeChat\Message\Formatter;

class SysMessage extends Message
{
    public function handleMessage() {
        $this->string = $this->content;
    }

    public function isRedPacket() {
        return stripos($this->string(), "红包") !== false;
    }
}