<?php
/**
 * Created by PhpStorm.
 * User: linyulin
 * Date: 2019/4/6
 * Time: 2:10 PM
 */

namespace Im050\WeChat\Providers;


use Im050\WeChat\Core\Application;

interface ServiceProvider
{
    /**
     * @param Application $application
     * @return void
     */
    public function register(Application $application);
}