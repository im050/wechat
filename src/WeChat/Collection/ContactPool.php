<?php
namespace Im050\WeChat\Collection;


class ContactPool implements Collection
{
    use PoolManager;

    const GROUP_POOR = 'group';

    const PUBLIC_USER_POOR = 'public_user';

    const CONTACT_POOR = 'contact';

    protected static $_instance = null;

    public $list = [];

    public $public_user_list = [];

    public $contact_list = [];

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


}