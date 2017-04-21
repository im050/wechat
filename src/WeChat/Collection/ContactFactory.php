<?php
/**
 * Created by PhpStorm.
 * User: linyulin
 * Date: 17/4/18
 * Time: 下午11:05
 */

namespace Im050\WeChat\Collection;

use Im050\WeChat\Collection\Element\Group;
use Im050\WeChat\Collection\Element\PublicUser;
use Im050\WeChat\Collection\Element\Contact;

class ContactFactory
{

    public static function create($item) {
        if (substr($item['UserName'], 0, 2) == "@@") {
            //群聊
            return new Group($item);
        } else {
            if (($item['VerifyFlag'] & 8) != 0) {
                //公众号
                return new PublicUser($item);
            } else {
                return new Contact($item);
            }
        }
    }

}