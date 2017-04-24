<?php
/**
 * Created by PhpStorm.
 * User: linyulin
 * Date: 17/4/24
 * Time: 上午11:08
 */

namespace Im050\WeChat\Collection;


use Illuminate\Support\Collection;

class ContactCollection extends Collection
{

    public function getContactByUserName($username)
    {
        $member = $this->offsetGet($username);
        if (!empty($user_item))
            return ContactFactory::create($member);
        return null;
    }

    public function getContactByAlias($alias)
    {
        $member = $this->find($alias, 'Alias', true);
        if (!empty($member)) {
            return ContactFactory::create($member);
        }
        return null;
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

}