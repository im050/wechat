<?php
namespace Im050\WeChat\Core;


class SyncKey
{
    public $syncKey = [];

    public $count = 0;

    /**
     * 设置SyncKey
     *
     * @param $syncKey
     */
    public function setSyncKey($syncKey)
    {
        if (is_array($syncKey)) {
            $this->syncKey = $syncKey;
        } else {
            $this->syncKey = $this->parse($syncKey);
        }
        $this->count = count($this->syncKey);
    }

    /**
     * 得到SyncKey
     *
     * @return array
     */
    public function get()
    {
        return $this->syncKey;
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
        foreach ($this->syncKey as $key => $item) {
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