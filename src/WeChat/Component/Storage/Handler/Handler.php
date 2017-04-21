<?php
/**
 * Created by PhpStorm.
 * User: linyulin
 * Date: 17/4/21
 * Time: 上午10:52
 */

namespace Im050\WeChat\Component\Storage\Handler;


interface Handler
{
    /*
     * 装在已存在的数据
     */
    public function load();

    /**
     * 保存装载的数据
     *
     * @return mixed
     */
    public function save();

    /**
     * 根据键名获取数据
     *
     * @return mixed
     */
    public function get($key);

    /**
     * 设置数据
     *
     * @param $key
     * @param $data
     * @return mixed
     */
    public function set($key, $data);

    public function setMultiple($array);

    public function getOriginData();
}