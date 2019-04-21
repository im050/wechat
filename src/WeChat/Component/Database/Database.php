<?php
/**
 * Created by PhpStorm.
 * User: linyulin
 * Date: 2019/4/13
 * Time: 8:33 PM
 */

namespace Im050\WeChat\Component\Database;

use Im050\WeChat\Component\Console;
use Medoo\Medoo;

class Database extends Medoo
{

    private $name = 'default';

    private $maxRetryTimes = 3;

    public function __construct(array $options, string $name)
    {
        parent::__construct($options);
        $this->name = $name;
    }

    public function reconnect()
    {
        $config = config('db.' . $this->name);
        $this->__construct($config, $this->name); //init.
        Console::log("Triggered reconnection database mechanism.", Console::DEBUG);
    }

    public function exec($query, $map = [])
    {
        if ($this->maxRetryTimes <= 0) {
            throw new \PDOException("MySQL has gone away", 'HY000');
        }
        try {
            $result = parent::exec($query, $map);
            $this->maxRetryTimes < 5 && $this->maxRetryTimes++;
            return $result;
        } catch (\PDOException $e) {
            if ($e->getCode() == 'HY000') {
                $this->reconnect();
                $this->maxRetryTimes--;
                return $this->exec($query, $map);
            }
            throw $e;
        }
    }
}