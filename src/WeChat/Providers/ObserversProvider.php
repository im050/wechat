<?php
/**
 * Created by PhpStorm.
 * User: linyulin
 * Date: 2019/4/6
 * Time: 2:11 PM
 */

namespace Im050\WeChat\Providers;


use Im050\WeChat\Core\Application;
use Im050\WeChat\Observers\LoginSuccessObserver;
use Im050\WeChat\Observers\LogoutObserver;
use Im050\WeChat\Observers\MessageObserver;

class ObserversProvider implements ServiceProvider
{
    /**
     * @param Application $application
     * @return void
     */
    public function register(Application $application)
    {
        $application->singleton('loginSuccessObserver', function() {
            return new LoginSuccessObserver();
        });

        $application->singleton('messageObserver', function() {
            return new MessageObserver();
        });

        $application->singleton('logoutObserver', function() {
            return new LogoutObserver();
        });
    }

}