<?php
namespace Im050\WeChat\Core;


class Account
{

    public $uin;

    public $username;

    public $nickname;

    public $sex;

    protected static $_instance = null;

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

    public function setUser($user) {
        $this->nickname = $user['NickName'];
        $this->username = $user['UserName'];
        $this->sex = $user['Sex'];
        $this->uin = $user['Uin'];
    }

    public static function username() {
        return self::getInstance()->username;
    }

    public static function nickname() {
        return self::getInstance()->nickname;
    }

    public static function uin() {
        return self::getInstance()->uin;
    }

    public static function __callStatic($name, $arguments)
    {
        $account = self::getInstance();
        if (isset($account->$name)) {
            return $account->$name;
        } else {
            return null;
        }
    }

}