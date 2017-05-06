<?php
/**
 * Created by PhpStorm.
 * User: linyulin
 * Date: 17/4/15
 * Time: 下午7:23
 */

namespace Im050\WeChat\Component;

class Console
{

    const INFO = 'INFO';

    const WARNING = 'WARNING';

    const ERROR = 'ERROR';

    const MESSAGE = 'MESSAGE';

    const DEBUG = 'DEBUG';

    public static function log($message, $level = 'INFO')
    {
        $string = "[" . date("Y-m-d H:i:s", time()) . "][" . $level . "] " . $message . PHP_EOL;
        echo $string;
        if ($level == self::WARNING) {
            if (config('debug')) {
                $log = [
                    '错误说明' => $string,
                    '日志时间' => Utils::now()
                ];

                Logger::write($log, config('warning_log_path'));
            }
        }
        if ($level == self::ERROR) {
            exit;
        }
    }

}