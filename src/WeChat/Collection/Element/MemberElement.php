<?php
/**
 * Created by PhpStorm.
 * User: linyulin
 * Date: 17/4/18
 * Time: 下午3:19
 */

namespace Im050\WeChat\Collection\Element;

use Im050\WeChat\Task\TaskQueue;

class MemberElement extends Element
{

    public function __construct($obj)
    {
        parent::__construct($obj);
        if (!isset($this->obj['RemarkName']) || $this->obj['RemarkName'] == '') {
            $this->obj['RemarkName'] = $this->obj['NickName'];
        }
    }

    public function getRemarkName()
    {
        return $this->obj['RemarkName'];
    }

    public function getUserName()
    {
        return $this->obj['UserName'];
    }

    public function sendMessage($text, $blocking = false)
    {
        if ($blocking == false) {
            TaskQueue::run('SendMessage', [
                'username' => $this->getUserName(),
                'content' => $text
            ]);
        } else {
            app()->api->sendMessage($this->getUserName(), $text);
        }
    }

    public function __toString()
    {
        return (string)$this->getRemarkName();
    }

}