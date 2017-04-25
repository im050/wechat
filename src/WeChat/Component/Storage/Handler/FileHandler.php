<?php
/**
 * Created by PhpStorm.
 * User: linyulin
 * Date: 17/4/21
 * Time: 上午10:51
 */

namespace Im050\WeChat\Component\Storage\Handler;

use Im050\WeChat\Component\Utils;

class FileHandler implements Handler
{

    use CommonHandler;

    public $config = [];

    protected $data = [];

    public function __construct($config = array())
    {
        $this->config = $config;

        if (!isset($config['path'])) {
            throw new \Exception('未指定 FileHandle 存放路径');
        }

        $this->load();
    }


    public function load()
    {
        $path = $this->config['path'];

        if (file_exists($path)) {
            $content = file_get_contents($path, LOCK_SH);
            $content = Utils::json_decode($content);
        } else {
            $content = [];
        }

        $this->data = $content;
    }

    /**
     * 保存装载的数据
     *
     * @return void
     */
    public function save()
    {
        $path = $this->config['path'];
        file_put_contents($path, Utils::json_encode($this->data), LOCK_EX);
    }

    public function __destruct()
    {
        //
    }

}