<?php
/**
 * Created by PhpStorm.
 * User: linyulin
 * Date: 2019/4/9
 * Time: 2:29 PM
 */

namespace Im050\WeChat\Crontab;


class Mission
{
    private $cronString;

    private $callback;

    /**
     * @return mixed
     */
    public function getCronString()
    {
        return $this->cronString;
    }

    /**
     * @param mixed $cronString
     * @return Mission
     */
    public function setCronString($cronString)
    {
        $this->cronString = $cronString;
        return $this;
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
     * @return Mission
     */
    public function setCallback(callable $callback)
    {
        $this->callback = $callback;
        return $this;
    }


}