<?php
/**
 * Created by PhpStorm.
 * User: linyulin
 * Date: 17/4/18
 * Time: 下午3:22
 */

namespace Im050\WeChat\Collection\Element;

class Group extends MemberElement
{

    /**
     * 成员处理
     *
     * @param $element
     */
    public function handleElement($element)
    {
        if ($element['RemarkName'] == '') {
            $element['RemarkName'] = '未知群';
        }
    }

    public function getMemberList()
    {
        return [];
    }
}