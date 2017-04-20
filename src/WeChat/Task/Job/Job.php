<?php
/**
 * Created by PhpStorm.
 * User: linyulin
 * Date: 17/4/19
 * Time: 下午3:04
 */

namespace Im050\WeChat\Task\Job;


class Job
{

    public $params = [];

    public function __construct($params)
    {
        $this->params = $params;
    }

    public function run()
    {
        return true;
    }

    public function __get($param)
    {
        if (isset($this->$param)) {
            return $this->$param;
        } else if (isset($this->params[$param])) {
            return $this->params[$param];
        } else {
            return null;
        }
    }
}