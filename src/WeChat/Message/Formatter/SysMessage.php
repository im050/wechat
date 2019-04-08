<?php
namespace Im050\WeChat\Message\Formatter;

class SysMessage extends Message
{
    public function handleMessage()
    {
        $this->string = $this->content;
    }
}