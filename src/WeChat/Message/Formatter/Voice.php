<?php
/**
 * Created by PhpStorm.
 * User: linyulin
 * Date: 17/4/20
 * Time: 下午2:38
 */

namespace Im050\WeChat\Message\Formatter;


class Voice extends Message
{
    public function handleMessage()
    {
        $this->string = "语音";
    }
}