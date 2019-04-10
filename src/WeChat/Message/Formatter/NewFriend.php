<?php
/**
 * Created by PhpStorm.
 * User: linyulin
 * Date: 2019/4/8
 * Time: 5:45 PM
 */

namespace Im050\WeChat\Message\Formatter;


class NewFriend extends SysMessage
{
    public function approve() {
        $message = $this->raw();
        app()->api->approve($message['RecommendInfo']['UserName'], $message['RecommendInfo']['Ticket']);
    }
}