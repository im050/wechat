<?php
/**
 * Created by PhpStorm.
 * User: linyulin
 * Date: 17/4/24
 * Time: 上午11:08
 */

namespace Im050\WeChat\Collection;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Im050\WeChat\Collection\Element\MemberElement;

class ContactCollection extends Collection
{

    /**
     * 根据用户名获取具体联系人实例
     *
     * @param $username
     * @return MemberElement
     */
    public function getContactByUserName($username)
    {
        $member = @$this->offsetGet($username);
        if (!empty($member))
            return ContactFactory::create($member);
        else {
            try {
                $contact = $this->findContactFromAPI($username);
            } catch (\Exception $e) {
                app()->log->debug("find contact failed.", $e);
                return null;
            }
            if ($contact != null) {
                $this->put($username, $contact);
                return ContactFactory::create($contact);
            }
        }
        return null;
    }

    /**
     * 通过username获取成员详细信息
     *
     * @param $username
     * @return mixed|null
     */
    public function findContactFromAPI($username)
    {
        $data = app()->api->getBatchContact($username);
        $contactList = $data['ContactList'];
        if (!empty($contactList)) {
            return reset($contactList);
        } else {
            return null;
        }
    }

    /**
     * 根据字段搜索
     *
     * @param $field
     * @param $value
     * @param bool $blur
     * @return MemberElement|null
     */
    public function getContactByFields($field, $value, $blur = false)
    {
        $member = $this->find($value, $field, true, $blur);
        if (!empty($member)) {
            return ContactFactory::create($member);
        }
        return null;
    }

    /**
     * 根据微信账号获取具体联系人实例
     *
     * @param $alias
     * @param $blur
     * @return MemberElement|null
     */
    public function getContactByAlias($alias, $blur = false)
    {
        return $this->getContactByFields("Alias", $alias, $blur);
    }

    /**
     * 根据备注获取用户
     *
     * @param $remarkName
     * @param $blur
     * @return MemberElement|null
     */
    public function getContactByRemarkName($remarkName, $blur = false)
    {
        return $this->getContactByFields("RemarkName", $remarkName, $blur);
    }

    /**
     * 根据NickName获取群
     *
     * @param $nickName
     * @param $blur
     * @return MemberElement|null
     */
    public function getContactByNickName($nickName, $blur = false)
    {
        return $this->getContactByFields("NickName", $nickName, $blur);
    }

    /**
     * 获取女性联系人
     *
     * @return ContactCollection|mixed
     */
    public function getFemaleContacts()
    {
        return $this->find(2, 'Sex', false);
    }

    /**
     * 获取男性联系人
     *
     * @return ContactCollection|mixed
     */
    public function getMaleContacts()
    {
        return $this->find(1, 'Sex', false);
    }

    /**
     * 获取未知性别联系人
     *
     * @return ContactCollection|mixed
     */
    public function getUnknownSexContacts()
    {
        return $this->find(0, 'Sex', false);
    }

    /**
     * @param $search
     * @param $key
     * @param bool $first
     * @param bool $blur
     * @return mixed
     */
    public function find($search, $key, $first = false, $blur = false)
    {
        $objects = $this->filter(function ($item) use ($search, $key, $blur) {
            if (!isset($item[$key])) return false;
            if ($blur && Str::contains($item[$key], $search)) {
                return true;
            } elseif (!$blur && $item[$key] === $search) {
                return true;
            }
            return false;
        });

        return $first ? $objects->first() : $objects;
    }

    /**
     * 遍历元素
     *
     * @param callable $callback
     * @return $this
     */
    public function each(callable $callback)
    {
        foreach ($this->items as $key => $item) {
            if ($callback(ContactFactory::create($item), $key) === false) {
                break;
            }
        }
        return $this;
    }

}