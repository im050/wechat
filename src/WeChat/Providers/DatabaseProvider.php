<?php
/**
 * Created by PhpStorm.
 * User: linyulin
 * Date: 2019/4/13
 * Time: 8:37 PM
 */

namespace Im050\WeChat\Providers;


use Im050\WeChat\Component\Database;
use Im050\WeChat\Core\Application;

class DatabaseProvider implements ServiceProvider
{
    /**
     * @param Application $application
     * @return void
     */
    public function register(Application $application)
    {
        $application->singleton('database', function() {
            return new Database();
        });
    }

}