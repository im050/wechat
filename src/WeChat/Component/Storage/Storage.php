<?php
/**
 * Created by PhpStorm.
 * User: linyulin
 * Date: 17/4/21
 * Time: 上午10:51
 */

namespace Im050\WeChat\Component\Storage;


use Im050\WeChat\Component\Storage\Handler\FileHandler;
use Im050\WeChat\Component\Storage\Handler\Handler;

class Storage
{

    public $handler = null;

    public function __construct(Handler $handler = null)
    {
        if ($handler == null) {
            $handler = new FileHandler();
        }

        $this->handler = $handler;
    }

    public function setHandler(Handler $handler)
    {
        $handler->setMultiple($this->handler->getOriginData());
        $this->handler = $handler;
    }

    public function __call($name, $arguments)
    {
        if (method_exists($this->handler, $name)) {
            return call_user_func_array(array(
                $this->handler, $name
            ),
                $arguments);
        } else {
            throw new \Exception("不存在的 Handler 方法");
        }
    }
}