<?php
/**
 * Created by PhpStorm.
 * User: linyulin
 * Date: 2019/4/22
 * Time: 4:10 AM
 */

namespace Im050\WeChat\Component\Database;

use PDO;

class DatabaseFactory
{
    private $dbInstances = [];

    public function __construct()
    {
    }

    /**
     * @param string $name
     * @return Database
     */
    public function getConnection($name = 'default') {
        $realName = $this->getInstanceName($name);
        if (array_key_exists($realName, $this->dbInstances)) {
            return $this->dbInstances[$realName];
        }
        $config = config("db." . $name);
        !isset($config['option']) && $config['option'] = [];
        $config['option'] = $config['option'] + [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION];
        $this->dbInstances[$realName] = new Database($config, $name);
        return $this->dbInstances[$realName];
    }

    private function getInstanceName($name) {
        $pid = posix_getpid();
        $realName = $name . "_" . $pid;
        return $realName;
    }
}