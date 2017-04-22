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

    public $element = [];

    public function __construct($element)
    {
        if (empty($element)) {
            return;
        }

        $this->element = $element;
    }

    public function __get($params) {
        if (isset($this->element[$params])) {
            return $this->element[$params];
        } else if (isset($this->$params)) {
            return $this->$params;
        } else {
            return null;
        }
    }

}