<?php
namespace Im050\WeChat\Message\Formatter;

class Text extends Message
{
    public function handleMessage() {
        $this->string = $this->content;
    }
}