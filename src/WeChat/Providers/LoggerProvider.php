<?php
/**
 * Created by PhpStorm.
 * User: linyulin
 * Date: 2019/4/6
 * Time: 2:11 PM
 */

namespace Im050\WeChat\Providers;

use Im050\WeChat\Collection\Members;
use Im050\WeChat\Collection\MessageCollection;
use Im050\WeChat\Component\HttpClient;
use Im050\WeChat\Component\Storage\Handler\FileHandler;
use Im050\WeChat\Component\Storage\Storage;
use Im050\WeChat\Core\Account;
use Im050\WeChat\Core\Api;
use Im050\WeChat\Core\Application;
use Im050\WeChat\Core\Auth;
use Im050\WeChat\Core\SyncKey;
use Im050\WeChat\Message\MessageHandler;
use Im050\WeChat\Task\TaskQueue;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

class LoggerProvider implements ServiceProvider
{
    /**
     * @param Application $application
     * @return void
     */
    public function register(Application $application)
    {
        $dateFormat = "Y-m-d H:i:s";
        $formatter = new LineFormatter(null,  $dateFormat);

        $application->singleton('log', function () use($formatter) {
            $log = new Logger('robot');
            $log->pushHandler((new StreamHandler(config('log.path') . 'robot.log', config("log_level")))->setFormatter($formatter));
            return $log;
        });

        $application->singleton('messageLog', function () use($formatter) {
            $log = new Logger('message');
            $log->pushHandler((new StreamHandler(config('log.message_log_path') . 'message.log', config("log_level")))->setFormatter($formatter));
            return $log;
        });
    }

}