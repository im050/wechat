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

    public function __construct($element)
    {
        parent::__construct($element);
        if (!isset($this->element['RemarkName']) || $this->element['RemarkName'] == '') {
            $this->element['RemarkName'] = $this->element['NickName'];
        }
        $this->handleElement();
    }

    public function handleElement() {
        //todo: nothing.
    }

    public function getAlias() {
        return $this->element['Alias'];
    }

    public function getRemarkName()
    {
        return $this->element['RemarkName'];
    }

    public function getUserName()
    {
        return $this->element['UserName'];
    }

    /**
     * 发送消息
     *
     * @param $text
     * @param bool $blocking 是否阻塞，否则用任务队列进行发送消息
     */
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