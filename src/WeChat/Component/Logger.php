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

        $logString = '';

        foreach ($data as $column => $content) {
            $logString .= "[{$column}] {$content} " . PHP_EOL;
        }

        if (empty($logString)) {
            return false;
        }

        return FileSystem::append($logString, $file);
    }
}