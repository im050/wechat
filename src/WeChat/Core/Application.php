<?php
namespace Im050\WeChat\Core;
use Im050\WeChat\Collection\Members;
use Im050\WeChat\Collection\MessageCollection;
use Im050\WeChat\Component\Config;
use Im050\WeChat\Component\Database;
use Im050\WeChat\Component\HttpClient;
use Im050\WeChat\Crontab\Crontab;
use Im050\WeChat\Message\MessageHandler;
use Im050\WeChat\Providers\CoreProvider;
use Im050\WeChat\Providers\CrontabProvider;
use Im050\WeChat\Providers\DatabaseProvider;
use Im050\WeChat\Providers\LoggerProvider;
use Im050\WeChat\Providers\ObserversProvider;
use Im050\WeChat\Providers\ServiceProvider;
use Im050\WeChat\Task\TaskQueue;
use Im050\WeChat\Observers\LoginSuccessObserver;
use Monolog\Logger;


/**
 * Class Application
 *
 * @package Im050\WeChat\Core
 * @property Api $api
 * @property MessageHandler $message
 * @property TaskQueue $taskQueue
 * @property Auth $auth
 * @property Config $config
 * @property HttpClient $http
 * @property Members $members
 * @property SyncKey $syncKey
 * @property Account $account
 * @property MessageCollection $messageCollection
 * @property LoginSuccessObserver $loginSuccessObserver
 * @property \Im050\WeChat\Observers\MessageObserver $messageObserver
 * @property \Im050\WeChat\Observers\LogoutObserver $logoutObserver
 * @property Crontab $crontab
 * @property Logger $log
 * @property Logger $messageLog
 * @property Database $database
 */
class Application extends Container
{

    private static $instance = null;

    private $providers = [
        CoreProvider::class,
        ObserversProvider::class,
        CrontabProvider::class,
        LoggerProvider::class,
        DatabaseProvider::class
    ];

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function bootstrap() {
        $serviceProviders = config("robot.providers");
        if (!empty($serviceProviders) && is_array($serviceProviders)) {
            $this->providers = array_merge($this->providers, $serviceProviders);
        }
        foreach ($this->providers as $provider) {
            $class = (new $provider());
            if (!$class instanceof ServiceProvider) {
                continue;
            }
            $class->register($this);
        }
    }

    /**
     * 清理进程
     */
    public function clear()
    {
        //关闭任务进程
        TaskQueue::shutdown();
        //关闭心跳进程
        $this->message->heartProcess->exit(0);
        //关闭自己
        exit(0);
    }

}