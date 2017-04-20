<?php
/**
 * Created by PhpStorm.
 * User: linyulin
 * Date: 17/4/18
 * Time: 下午2:13
 */

namespace Im050\WeChat\Collection;


use Im050\WeChat\Collection\Element\Element;

interface Collection
{
    public function add(Element $item);

    public function getList();
}