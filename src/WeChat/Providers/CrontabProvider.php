<?php
/**
 * Created by PhpStorm.
 * User: linyulin
 * Date: 2019/4/6
 * Time: 2:11 PM
 */

namespace Im050\WeChat\Providers;

use Im050\WeChat\Core\Application;
use Im050\WeChat\Crontab\Crontab;

class CrontabProvider implements ServiceProvider
{
    /**
     * @param Application $application
     * @return void
     */
    public function register(Application $application)
    {
        $application->singleton("crontab", function () {
            return new Crontab();
        });
    }

}