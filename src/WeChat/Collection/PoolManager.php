<?php
/**
 * Created by PhpStorm.
 * User: linyulin
 * Date: 17/4/18
 * Time: 下午2:23
 */

namespace Im050\WeChat\Collection;


use Im050\WeChat\Collection\Element\Contact;
use Im050\WeChat\Collection\Element\Element;
use Im050\WeChat\Collection\Element\Group;
use Im050\WeChat\Collection\Element\PublicUser;

trait PoolManager
{

    public function add(Element $item) {

        $this->list[$item->UserName] = $item;

        if ($item instanceof Group) {
            $this->group_list[$item->UserName] = &$this->list[$item->UserName];
        } else if ($item instanceof PublicUser) {
            $this->public_user_list[$item->UserName] = &$this->list[$item->UserName];
        } else if ($item instanceof Contact) {
            $this->contact_list[$item->UserName] = &$this->list[$item->UserName];
        }
    }

    public function getByUserName($username) {
        return isset($this->list[$username]) ? $this->list[$username] : null;
    }

    public function getList() {
        return $this->list;
    }

    public function getRandom($poor_type = 'all') {
        switch($poor_type) {
            case ContactPool::CONTACT_POOR:
                $list = & $this->contact_list;
                break;
            case ContactPool::GROUP_POOR:
                $list = & $this->group_list;
                break;
            case ContactPool::PUBLIC_USER_POOR:
                $list = & $this->public_user_list;
                break;
        }
        $username = array_rand($list);
        $user = $this->getByUserName($username);
        return $user;
    }
}