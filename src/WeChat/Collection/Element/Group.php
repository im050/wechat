<?php
/**
 * Created by PhpStorm.
 * User: linyulin
 * Date: 17/4/18
 * Time: 下午3:22
 */

namespace Im050\WeChat\Collection\Element;

use Im050\WeChat\Collection\ContactFactory;

class Group extends MemberElement
{

    public $members = [];

    /**
     * 成员处理
     *
     */
    public function handleElement()
    {
        if (isset($this->element['MemberList'])) {
            $this->members = & $this->element['MemberList'];
        }
        if ($this->element['RemarkName'] == '') {
            $this->element['RemarkName'] = '未知群';
        }
    }


    public function getMemberByUserName($username) {
        foreach($this->members as $key => $value) {
            if ($value['UserName'] == $username) {
                return ContactFactory::create($value);
            }
        }
        return null;
    }

    /**
     * 设置成员
     *
     * @param $memberList
     */
    public function setMemberList($memberList) {
        $this->members = $memberList;
    }

    /**
     * 获取成员列表
     *
     * @return array
     */
    public function getMemberList()
    {
        return $this->members;
    }
}