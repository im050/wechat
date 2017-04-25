<?php
/**
 * Created by PhpStorm.
 * User: linyulin
 * Date: 17/4/18
 * Time: 下午11:05
 */

namespace Im050\WeChat\Collection;

use Im050\WeChat\Collection\Element\Group;
use Im050\WeChat\Collection\Element\Official;
use Im050\WeChat\Collection\Element\Contact;
use Im050\WeChat\Collection\Element\Special;

class ContactFactory
{

    protected static $_instance = [];

    /**
     * 创建联系人实例
     *
     * @param $item
     * @return mixed
     */
    public static function create($item) {
        $username = $item['UserName'];
        if (!isset(self::$_instance[$username]) || empty(self::$_instance[$username])) {
            switch(Members::getUserType($item)) {
                case Members::TYPE_CONTACT:
                    self::$_instance[$username] = new Contact($item);
                    break;
                case Members::TYPE_OFFICIAL:
                    self::$_instance[$username] = new Official($item);
                    break;
                case Members::TYPE_SPECIAL:
                    self::$_instance[$username] = new Special($item);
                    break;
                case Members::TYPE_GROUP:
                    self::$_instance[$username] = new Group($item);
                    break;
            }
        }
        return self::$_instance[$username];
    }
}