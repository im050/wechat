<?php
/**
 * Created by PhpStorm.
 * User: linyulin
 * Date: 2019/4/13
 * Time: 8:33 PM
 */

namespace Im050\WeChat\Component;


use Medoo\Medoo;

class Database
{
    private $dbInstances = [];

    public function __construct()
    {
    }

    /**
     * @param string $name
     * @return Medoo
     */
    public function getConnection($name = 'default') {
        $pid = posix_getpid();
        $realName = $name . "_" . $pid;
        if (array_key_exists($realName, $this->dbInstances)) {
            return $this->dbInstances[$realName];
        }
        $this->dbInstances[$realName] = new Medoo(config("db." . $name));
        return $this->dbInstances[$realName];
    }
}