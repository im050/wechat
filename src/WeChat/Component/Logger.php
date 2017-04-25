<?php
/**
 * Created by PhpStorm.
 * User: linyulin
 * Date: 17/4/25
 * Time: 下午2:53
 */

namespace Im050\WeChat\Component;


use Im050\WeChat\Core\FileSystem;

class Logger
{
    public static function write($log, $file)
    {
        if ($log instanceof \Exception) {
            $data = [
                '异常文件' => $log->getFile(),
                '异常段落' => $log->getLine(),
                '异常信息' => $log->getMessage()
            ];
        } else if (is_array($log)) {
            $data = $log;
        } else {
            return false;
        }

        $log_string = '';

        foreach ($data as $column => $content) {
            $log_string .= "[{$column}] {$content} " . PHP_EOL;
        }

        if (empty($log_string)) {
            return false;
        }

        return FileSystem::append($log_string, $file);
    }
}