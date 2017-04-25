<?php
namespace Im050\WeChat\Core;


class SyncKey
{
    public $sync_key = [];

    public $count = 0;

    public static $_instance = null;

    private function __construct()
    {
    }

    public static function getInstance()
    {
        if (self::$_instance === null) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * 设置SyncKey
     *
     * @param $sync_key
     */
    public function setSyncKey($sync_key)
    {
        if (is_array($sync_key)) {
            $this->sync_key = $sync_key;
        } else {
            $this->sync_key = $this->parse($sync_key);
        }
        $this->count = count($this->sync_key);
    }

    /**
     * 得到SyncKey
     *
     * @return array
     */
    public function get()
    {
        return $this->sync_key;
    }

    /**
     * 得到SyncKey数量
     *
     * @return int
     */
    public function count()
    {
        return $this->count;
    }

    /**
     * 获取文本形式的SyncKey
     *
     * @return string
     */
    public function string()
    {
        $string = '';
        foreach ($this->sync_key as $key => $item) {
            $string .= $item['Key'] . "_" . $item['Val'] . '|';
        }
        $string = rtrim($string, "|");
        return $string;
    }

    /**
     * 解析文本形式的SyncKey
     *
     * @param $string
     * @return array
     */
    public function parse($string)
    {
        $temp = explode("|", $string);
        $array = [];
        foreach ($temp as $key => $value) {
            $item = explode("_", $value);
            $array[] = [
                'Key' => $item[0],
                'Val' => $item[1]
            ];
        }
        return $array;
    }


}