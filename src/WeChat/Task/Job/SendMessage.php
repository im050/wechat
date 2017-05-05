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
        try {
            if (app()->api->sendMessage($username, $content)) {
                return true;
            } else {
                return false;
            }
        } catch (\Exception $e) {
            if (config('debug')) {
                $path = config('tmp_path') . '/log/exception.log';
                Logger::write($e, $path);
            }
            Console::log("发送消息失败, Exception: " . $e->getMessage(), Console::WARNING);
        }
        return true;
    }

}