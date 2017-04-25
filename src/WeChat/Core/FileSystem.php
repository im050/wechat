<?php
/**
 * Created by PhpStorm.
 * User: linyulin
 * Date: 17/4/25
 * Time: 下午3:18
 */

namespace Im050\WeChat\Core;


class FileSystem
{
    public static function checkFile($file) {
        $path = dirname($file);
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }
    }

    public static function write($content, $file, $flag = FILE_BINARY) {
        self::checkFile($file);
        return file_put_contents($file, $content . PHP_EOL, $flag);
    }

    public static function append($content, $file) {
        return self::write($content, $file, FILE_APPEND | LOCK_EX);
    }

    public static function download($url, $file) {
        $content = file_get_contents($url);
        return self::write($content, $file);
    }
}