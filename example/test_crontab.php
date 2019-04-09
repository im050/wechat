#!/usr/bin/env php
<?php
define('BASE_PATH', dirname(dirname(__FILE__)));

include(BASE_PATH . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php');

$crontab = new \Im050\WeChat\Crontab\Crontab();

$crontab->register("*/1 * * * *", function() {
    echo "hello" . time() . PHP_EOL;
});

$crontab->start();

\Swoole\Process::wait(true);