<?php
/**
 * Created by PhpStorm.
 * User: linyulin
 * Date: 17/4/18
 * Time: 下午3:09
 */

namespace Im050\WeChat\Collection\Element;


class Element
{

    public $obj = null;

    public function __construct($obj)
    {
        if (empty($obj)) {
            return;
        }

        $this->obj = $obj;
    }

    public function __get($params) {
        if (isset($this->obj[$params])) {
            return $this->obj[$params];
        } else if (isset($this->$params)) {
            return $this->$params;
        } else {
            return null;
        }
    }

}