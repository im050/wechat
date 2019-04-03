<?php
namespace Im050\WeChat\Core;

class Auth
{

    protected static $_instance = null;

    public $uuid = '';

    public $deviceId = '';

    public $sid = '';

    public $skey = '';

    public $passTicket = '';

    public $uin = '';

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

    /**
     * 设置UUID
     */
    public function setUuid($uuid)
    {
        $this->uuid = $uuid;
    }

    /**
     * 初始化授权信息
     * 例如pass_ticket, skey, sid
     *
     * @param array $token
     */
    public function setToken($token)
    {
        foreach($token as $key => $val) {
            $this->$key = $val;
            app()->keymap->set($key, $val);
        }
        app()->keymap->set('login_time', time())->save();
    }

    /**
     * 加载缓存的权限参数
     */
    public function loadTokenFromCache()
    {
        $need = ['sid', 'skey', 'uin', 'passTicket'];
        foreach ($need as $key) {
            $this->$key = app()->keymap->get($key);
        }
    }

}