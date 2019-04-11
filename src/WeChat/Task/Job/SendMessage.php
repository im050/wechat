<?php
/**
 * Created by PhpStorm.
 * User: linyulin
 * Date: 17/4/19
 * Time: 下午3:09
 */

namespace Im050\WeChat\Task\Job;

use Im050\WeChat\Component\Console;
use Im050\WeChat\Component\Logger;

class SendMessage extends Job
{
    public function run()
    {
        $username = $this->username;
        $content = $this->content;
        $type = $this->type;
        if ($type == null || $type == "") {
            $type = "text";
        }
        try {
            $flag = false;
            switch ($type) {
                case 'text':
                    $flag = app()->api->sendMessage($username, $content);
                    break;
                case 'image':
                    $file = $this->file;
                    $flag = app()->api->sendImage($username, $file);
                    break;
                case 'file':
                    $file = $this->file;
                    $flag = app()->api->sendFile($username, $file);
                    break;
                case 'emoticon':
                    $file = $this->file;
                    $flag = app()->api->sendEmoticon($username, $file);
                    break;
                default:
                    Console::log("不存在的发送数据类型, Type: {$type}", Console::WARNING);
                    break;
            }
            if ($flag) {
                return true;
            } else {
                return false;
            }
        } catch (\Exception $e) {
            Console::log("发送消息失败, Exception: " . $e->getMessage(), Console::WARNING);
        }
        return true;
    }

}