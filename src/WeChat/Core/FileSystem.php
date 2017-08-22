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
    /**
     * 检查路径是否存在
     *
     * @param $file
     */
    public static function checkFile($file)
    {
        $path = dirname($file);
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }
    }

    /**
     * 写入数据
     *
     * @param $content
     * @param $file
     * @param int $flag
     * @return int
     */
    public static function write($content, $file, $flag = FILE_BINARY)
    {
        self::checkFile($file);
        return file_put_contents($file, $content . PHP_EOL, $flag);
    }

    /**
     * 追加写入数据
     *
     * @param $content
     * @param $file
     * @return int
     */
    public static function append($content, $file)
    {
        return self::write($content, $file, FILE_APPEND | LOCK_EX);
    }

    /**
     * 根据url下载数据
     *
     * @param $url
     * @param $file
     * @return int
     */
    public static function download($url, $file)
    {
        $content = file_get_contents($url);
        return self::write($content, $file);
    }

    /**
     * 获取当前登录用户临时文件路径
     *
     * @return string
     */
    public static function getCurrentUserPath()
    {
        return config('tmp_path') . DIRECTORY_SEPARATOR . 'user' . DIRECTORY_SEPARATOR . app()->auth->uin;
    }

    /**
     * 获取图片存放路径
     *
     * @return string
     */
    public static function getImagePath() {
        return self::getCurrentUserPath() . DIRECTORY_SEPARATOR . '/images';
    }

    /**
     * 获取视频存放路径
     *
     * @return string
     */
    public static function getVideoPath() {
        return self::getCurrentUserPath() . DIRECTORY_SEPARATOR . '/video';
    }

    /**
     * 获取语音存放路径
     *
     * @return string
     */
    public static function getVoicePath() {
        return self::getCurrentUserPath() . DIRECTORY_SEPARATOR . '/voice';
    }

    /**
     * 保存图片
     *
     * @param $msg_id
     * @return bool|int
     */
    public static function saveImage($msg_id) {
        $api = app()->api;
        $image = $api->getMessageImage($msg_id);
        if (strlen($image) <= 0) {
            return false;
        }
        $file = self::getImagePath() . DIRECTORY_SEPARATOR . $msg_id . '.jpg';
        return self::write($image, $file);
    }

    /**
     * 保存语音
     *
     * @param $msg_id
     * @return bool|int
     */
    public static function saveVoice($msg_id) {
        $api = app()->api;
        $voice = $api->getMessageVoice($msg_id);
        if (strlen($voice) <= 0) {
            return false;
        }
        $file = self::getVoicePath() . DIRECTORY_SEPARATOR . $msg_id . '.mp3';
        return self::write($voice, $file);
    }

    /**
     * 保存视频
     *
     * @param $msg_id
     * @return bool|int
     */
    public static function saveVideo($msg_id) {
        $api = app()->api;
        $video = $api->getMessageVideo($msg_id);
        if (strlen($video) <= 0) {
            return false;
        }
        $file = self::getVideoPath() . DIRECTORY_SEPARATOR . $msg_id . '.mp4';
        return self::write($video, $file);
    }

    /**
     * 保存表情
     *
     * @param $msg_id
     * @return bool|int
     */
    public static function saveEmoticon($msg_id) {
        $api = app()->api;
        $image = $api->getMessageImage($msg_id);
        if (strlen($image) <= 0) {
            return false;
        }
        $file = self::getImagePath() . DIRECTORY_SEPARATOR . $msg_id . '.gif';
        return self::write($image, $file);
    }
}