<?php
/**
 * Created by PhpStorm.
 * User: linyulin
 * Date: 17/4/21
 * Time: 下午1:39
 */

namespace Im050\WeChat\Component\Storage\Handler;


trait CommonHandler
{

    /**
     * 根据键名获取数据
     *
     * @param $key
     * @return mixed
     */
    public function get($key)
    {
        if (isset($this->data[$key])) {
            return $this->data[$key];
        } else {
            return null;
        }
    }

    /**
     * 设置数据
     *
     * @param $key
     * @param $data
     * @return object
     */
    public function set($key, $data = [])
    {
        $this->data[$key] = $data;
        return $this;
    }

    /**
     * 获取元数据
     *
     * @return array
     */
    public function getOriginData()
    {
        return $this->data;
    }

    /**
     * 批量设置数据
     *
     * @param $array
     * @return $this
     */
    public function setMultiple($array)
    {
        $this->data = array_merge($this->data, $array);
        return $this;
    }

}