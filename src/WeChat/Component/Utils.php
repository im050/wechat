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

    const EMOJI_MAP = [
        '1f63c' => '1f601',
        '1f639' => '1f602',
        '1f63a' => '1f603',
        '1f4ab' => '1f616',
        '1f64d' => '1f614',
        '1f63b' => '1f60d',
        '1f63d' => '1f618',
        '1f64e' => '1f621',
        '1f63f' => '1f622',
    ];

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
     * @param bool $isNumber
     * @return string
     */
    public static function randomString($length = 16, $isNumber = false)
    {
        $numberPoor = "0123456789";
        $engPoor = "abcdefghijklmnopqrstuvwxyz";
        $string = '';
        if (!$isNumber) {
            $poor = $numberPoor . $engPoor;
        } else {
            $poor = $numberPoor;
        }
        for ($i = 0; $i < $length; $i++) {
            $random = mt_rand(0, strlen($poor) - 1);
            if (isset($poor{$random})) {
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
    public static function json_encode($data, $flag = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
    {
        return json_encode($data, $flag);
    }

    /**
     * JSON转数组
     *
     * @param string $json
     * @param int $flag
     * @return mixed
     */
    public static function json_decode($json, $flag = JSON_OBJECT_AS_ARRAY)
    {
        return json_decode($json, $flag);
    }

    /**
     * 获取当前时间
     *
     * @param string $timeZone
     * @param string $format
     * @return false|string
     */
    public static function now($timeZone = '', $format = 'Y-m-d H:i:s')
    {
        if (!empty($timeZone)) {
            date_default_timezone_set($timeZone);
        }
        return date($format, time());
    }

    /**
     * 获取随机文件名
     *
     * @param $path
     * @return bool|string
     */
    public static function getRandomFileName($path)
    {
        $files = scandir($path);
        unset($files[0], $files[1]);

        if (count($files)) {
            $file = $files[array_rand($files)];
        } else {
            return false;
        }

        return $path . DIRECTORY_SEPARATOR . $file;
    }

    /**
     * Emoji表情处理
     *
     * @param $content
     * @return mixed
     */
    public static function emojiTransfer($content)
    {
        // via Vbot
        $content = str_replace('<span class="emoji emoji1f450"></span', '<span class="emoji emoji1f450"></span>', $content);
        preg_match_all('/<span class="emoji emoji(.{1,10})"><\/span>/', $content, $match);

        foreach ($match[1] as &$unicode) {
            $unicode = array_get(self::EMOJI_MAP, $unicode, $unicode);
            if (strlen($unicode) > 5) {
                $unicode = "&#x" . substr($unicode, 0, 5) . ";&#x" . substr($unicode, 5, 10) . ";";
            } else {
                $unicode = "&#x{$unicode};";
            }
            $unicode = html_entity_decode($unicode);
        }
        return str_replace($match[0], $match[1], $content);
    }

    /**
     * HTML内容转换
     *
     * @param $content
     * @return mixed
     */
    public static function htmlTransfer($content)
    {
        return preg_replace('/\<br(\s*)?\/?\>/i', PHP_EOL, html_entity_decode($content));
    }

    /**
     * 格式化数据
     *
     * @param $content
     * @return string
     */
    public static function formatContent($content)
    {
        $content = self::emojiTransfer($content);
        $content = self::htmlTransfer($content);
        return $content;
    }

    /**
     * 计算机容量单位转换
     *
     * @param $size
     * @return string
     */
    public static function convert($size)
    {
        $unit = array('b', 'kb', 'mb', 'gb', 'tb', 'pb');
        return @round($size / pow(1024, ($i = floor(log($size, 1024)))), 2) . ' ' . $unit[$i];
    }


}