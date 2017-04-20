<?php
namespace Im050\WeChat\Collection;

use Im050\WeChat\Collection\Element\Contact;
use Im050\WeChat\Collection\Element\Element;
use Im050\WeChat\Collection\Element\Group;
use Im050\WeChat\Collection\Element\PublicUser;

class ContactPool implements Collection
{

    const GROUP_POOR = 'group';

    const PUBLIC_USER_POOR = 'public_user';

    const CONTACT_POOR = 'contact';

    protected static $_instance = null;

    public $list = [];

    public $public_user_list = [];

    public $contact_list = [];

    public $group_list = [];

    public $special_users = ['newsapp', 'fmessage', 'filehelper', 'weibo', 'qqmail',
        'fmessage', 'tmessage', 'qmessage', 'qqsync', 'floatbottle', 'lbsapp', 'shakeapp',
        'medianote', 'qqfriend', 'readerapp', 'blogapp', 'facebookapp', 'masssendapp',
        'meishiapp', 'feedsapp', 'voip', 'blogappweixin', 'weixin', 'brandsessionholder',
        'weixinreminder', 'wxid_novlwrv3lqwv11', 'gh_22b87fa7cb3c', 'officialaccounts',
        'notification_messages', 'wxid_novlwrv3lqwv11', 'gh_22b87fa7cb3c', 'wxitil',
        'userexperience_alarm', 'notification_messages'
    ];

    private function __construct()
    {
    }

    public static function getInstance()
    {
        if (self::$_instance === null) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

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
            default:
                $list = & $this->list;
        }
        $username = array_rand($list);
        $user = $this->getByUserName($username);
        return $user;
    }


}