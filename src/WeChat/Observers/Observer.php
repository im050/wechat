<?php
/**
 * Created by PhpStorm.
 * User: linyulin
 * Date: 2019/4/6
 * Time: 1:35 PM
 */

namespace Im050\WeChat\Observers;

class Observer implements ObserverInterface
{

    protected $callback;

    /**
     * @return mixed
     */
    public function trigger()
    {
        if (!is_callable($this->callback)) {
            return null;
        }
        $args = func_get_args();
        return call_user_func_array($this->callback, $args);
    }

    /**
     * @return mixed
     */
    public function getCallback()
    {
        return $this->callback;
    }

    /**
     * @param mixed $callback
     * @return Observer
     */
    public function setCallback($callback)
    {
        $this->callback = $callback;
        return $this;
    }



}