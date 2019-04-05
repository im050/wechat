<?php
namespace Im050\WeChat\Core;


class Account
{
    public $uin;

    public $username;

    public $nickname;

    public $sex;

    public function setUser($user) {
        $this->nickname = $user['NickName'];
        $this->username = $user['UserName'];
        $this->sex = $user['Sex'];
        $this->uin = $user['Uin'];
    }

    public static function username() {
        return app()->account->username;
    }

    public static function nickname() {
        return app()->account->nickname;
    }

    public static function uin() {
        return app()->account->uin;
    }
}