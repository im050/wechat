<?php
namespace Im050\WeChat\Message\Formatter;

class Text extends Message
{

    public $isAt = false;

    public function isAt() {
        return $this->isAt;
    }

    public function handleMessage()
    {
        $this->isAt = str_contains($this->content, '@' . Account::nickname());
        $this->string = $this->content;
    }
}