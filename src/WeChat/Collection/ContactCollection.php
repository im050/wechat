<?php
/**
 * Created by PhpStorm.
 * User: linyulin
 * Date: 17/4/24
 * Time: 上午11:08
 */

namespace Im050\WeChat\Collection;


use Illuminate\Support\Collection;
use Im050\WeChat\Component\Logger;

class ContactCollection extends Collection
{

    /**
     * 根据用户名获取具体联系人实例
     *
     * @param $username
     * @return mixed|null
     */
    public function getContactByUserName($username)
    {
        $member = $this->offsetGet($username);
        if (!empty($member))
            return ContactFactory::create($member);
        else {
            try {
                $contact = $this->findContactFromAPI($username);
            } catch (\Exception $e) {
                if (config('debug')) {
                    $path = config('exception_log_path');
                    Logger::write($e, $path);
                }
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
        $contact_list = $data['ContactList'];
        if (!empty($contact_list)) {
            return reset($contact_list);
        } else {
            return null;
        }
    }

    /**
     * 根据微信账号获取具体联系人实例
     *
     * @param $alias
     * @return mixed|null
     */
    public function getContactByAlias($alias)
    {
        $member = $this->find($alias, 'Alias', true);
        if (!empty($member)) {
            return ContactFactory::create($member);
        }
        return null;
    }

    /**
     * 获取女性联系人
     *
     * @return ContactCollection|mixed
     */
    public function getFemaleContacts() {
        return $this->find(2, 'Sex', false);
    }

    /**
     * 获取男性联系人
     *
     * @return ContactCollection|mixed
     */
    public function getMaleContacts() {
        return $this->find(1, 'Sex', false);
    }

    /**
     * 获取未知性别联系人
     *
     * @return ContactCollection|mixed
     */
    public function getUnknownSexContacts() {
        return $this->find(0, 'Sex', false);
    }

    /**
     * 获取整个数组
     *
     * @param $search
     * @param $key
     * @param bool $first
     * @param bool $blur
     * @return mixed|static
     *
     * @link http://github.com/HanSon/Vbot
     * @via Vbot
     */
    public function find($search, $key, $first = false, $blur = false)
    {
        $objects = $this->filter(function ($item) use ($search, $key, $blur) {

            if (!isset($item[$key])) return false;

            if ($blur && str_contains($item[$key], $search)) {
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