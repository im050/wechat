<?php
namespace Im050\WeChat\Component;


class Utils
{
    public static function xmlToArray($xml)
    {
        libxml_disable_entity_loader(true);
        $values = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        return $values;
    }

    public static function timeStamp()
    {
        return round(microtime(true) * 1000);
    }

    public static function isWin()
    {
        return strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
    }

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
            $string .= $poor{$random};
        }
        return $string;
    }

    public static function generateDeviceID()
    {
        return 'e' . Utils::randomString(15, true);
    }

}