<?php
namespace Im050\WeChat\Component;

/**
 * Class Utils
 * 工具方法类
 *
 * @package Im050\WeChat\Component
 */
class Utils
{

    /**
     * XML转数组
     *
     * @param $xml
     * @return mixed
     */
    public static function xmlToArray($xml)
    {
        libxml_disable_entity_loader(true);
        $values = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        return $values;
    }

    /**
     * 时间戳
     *
     * @return float
     */
    public static function timeStamp()
    {
        return round(microtime(true) * 1000);
    }

    /**
     * 判断是否win
     *
     * @return bool
     */
    public static function isWin()
    {
        return strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
    }

    /**
     * 生成随机字符
     *
     * @param int $length
     * @param bool $is_number
     * @return string
     */
    public static function randomString($length = 16, $is_number = false)
    {
        $number_poor = "0123456789";
        $eng_poor = "abcdefghijklmnopqrstuvwxyz";
        $string = '';
        if (!$is_number) {
            $poor = $number_poor . $eng_poor;
        } else {
            $poor = $number_poor;
        }
        for ($i = 0; $i < $length; $i++) {
            $random = mt_rand(0, strlen($poor));
            if (isset($poor{$random}) && empty($poor{$random})) {
                $string .= $poor{$random};
            }
        }
        return $string;
    }

    /**
     * 生成设备号
     *
     * @return string
     */
    public static function generateDeviceID()
    {
        return 'e' . Utils::randomString(15, true);
    }

    /**
     * 数组转JSON
     *
     * @param array $data
     * @param int $flag
     * @return string
     */
    public static function json_encode($data, $flag = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) {
        return json_encode($data, $flag);
    }

    /**
     * JSON转数组
     *
     * @param string $json
     * @param int $flag
     * @return mixed
     */
    public static function json_decode($json, $flag = JSON_OBJECT_AS_ARRAY) {
        return json_decode($json, $flag);
    }

    /**
     * 获取当前时间
     *
     * @param string $time_zone
     * @param string $format
     * @return false|string
     */
    public static function now($time_zone = '', $format = 'Y-m-d H:i:s') {
        if (!empty($time_zone)) {
            date_default_timezone_set($time_zone);
        }
        return date($format, time());
    }
}