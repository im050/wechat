<?php
namespace Im050\WeChat\Core;
use Im050\WeChat\Collection\Members;
use Im050\WeChat\Collection\MessageCollection;
use Im050\WeChat\Component\Config;
use Im050\WeChat\Component\HttpClient;
use Im050\WeChat\Component\Storage\Handler\FileHandler;
use Im050\WeChat\Component\Storage\Storage;
use Im050\WeChat\Message\MessageHandler;
use Im050\WeChat\Observers\MessageObserver;
use Im050\WeChat\Providers\CoreProvider;
use Im050\WeChat\Providers\ObserversProvider;
use Im050\WeChat\Providers\ServiceProvider;
use Im050\WeChat\Task\TaskQueue;
use Im050\WeChat\Observers\LoginSuccessObserver;
use Im050\WeChat\Observers\LogoutObserver;


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
 */
class Application extends Container
{

    private static $instance = null;

    private $providers = [
        CoreProvider::class,
        ObserversProvider::class
    ];

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function bootstrap() {
        $serviceProviders = config("service_providers");
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

}