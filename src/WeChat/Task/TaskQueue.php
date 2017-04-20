<?php
/**
 * Created by PhpStorm.
 * User: linyulin
 * Date: 17/4/19
 * Time: 下午5:43
 */

namespace Im050\WeChat\Task;

use Im050\WeChat\Component\Console;
use Im050\WeChat\Task\Job\Job;

class TaskQueue
{

    public $config = [
        'max_process_num' => 10
    ];

    public $process_num = 0;

    public $process_pool = [];

    public function __construct($config = array())
    {
        $this->config = array_merge($this->config, $config);
        $this->process_num = $this->config['max_process_num'];
    }

    public function createProcess()
    {
        for ($i = 0; $i < $this->process_num; $i++) {
            $process = new \swoole_process(array($this, "onTask"));
            $process->useQueue();
            $pid = $process->start();
            $this->process_pool[$pid] = &$process;
        }
    }

    public function onTask(\swoole_process $worker)
    {
        while (($data = $worker->pop()) !== false) {
            $data = json_decode($data, JSON_OBJECT_AS_ARRAY);
            $job = $data['job'];
            $params = $data['params'];
            $class = __NAMESPACE__ . '\\Job\\' . $job;

            if (!class_exists($class)) {
                throw new \Exception("任务不存在");
            }

            $instance = new $class($params);

            if ($instance instanceof Job) {
                //运行
                $instance->run();
            }

            unset($instance);
        }

        $worker->exit(0);
    }

    public function task($job, $params)
    {
        //延迟创建进程
        if (count($this->process_pool) <= 0) {
            $this->createProcess();
        }
        $process = current($this->process_pool);
        $data = array(
            'job' => $job,
            'params' => $params
        );
        $data = json_encode($data, JSON_UNESCAPED_UNICODE);
        $process->push($data);
    }

    public static function run($job, $params)
    {
        if (app()->hasInstance('task_queue')) {
            app()->get('task_queue')->task($job, $params);
        } else {
            Console::log("尚未创建任务队列");
        }
    }


}