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

class CoreProvider implements ServiceProvider
{
    /**
     * @param Application $application
     * @return void
     */
    public function register(Application $application)
    {
        $application->singleton("http", function () {
            return new HttpClient();
        });

        // auth for wechat login
        $application->singleton("auth", function () {
            return new Auth();
        });

        // init wechat api operator
        $application->singleton('api', function () {
            return new Api();
        });

        // account
        $application->singleton('account', function() {
            return new Account();
        });

        // Sync Key
        $application->singleton('syncKey', function() {
            return new SyncKey();
        });

        // message handler
        $application->singleton('message', function () {
            return new MessageHandler();
        });

        // message collection
        $application->singleton('messageCollection', function() {
            return new MessageCollection(config('mc_items'));
        });

        // task queue
        $application->singleton('taskQueue', function () {
            return new TaskQueue([
                'max_process_num' => config('task_process_num')
            ]);
        });

        // member collection
        $application->singleton('members', function() {
            return new Members();
        });

        // keymap for manage auth info.
        $application->singleton('keymap', function () {
            $config = app()->config;
            $tmpPath = $config->get('tmp_path');
            return new Storage(new FileHandler([
                'file' => $tmpPath . DIRECTORY_SEPARATOR . 'keymap.json'
            ]));
        });
    }

}