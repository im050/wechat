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
use Im050\WeChat\Component\Utils;

class RobotReply extends Job
{
    public function run()
    {
        $payload = [
            'key'    => '9aff046608594aa68d6774ad41020951',
            'info'   => $this->from_message,
            'userid' => $this->userid
        ];

        try {
            $content = http()->get('http://www.tuling123.com/openapi/api', $payload);
        } catch (\Exception $e) {
            if (config('debug')) {
                Logger::write($e, config('exception_log_path'));
            }
            Console::log("请求图灵接口失败, Exception: " . $e->getMessage(), Console::WARNING);
            return false;
        }
        $username = $this->username;
        try {
            $content = Utils::json_decode($content);
            $content = $content['text'];
            if (app()->api->sendMessage($username, $content)) {
                return true;
            } else {
                return false;
            }
        } catch (\Exception $e) {
            if (config('debug')) {
                $path = config('exception_log_path');
                Logger::write($e, $path);
            }
            Console::log("发送消息失败, Exception: " . $e->getMessage(), Console::WARNING);
        }
        return true;
    }
}