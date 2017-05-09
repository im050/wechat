<?php
/**
 * TaskQueue
 *
 * 基于Swoole_process实现生产者消费者队列任务
 */

namespace Im050\WeChat\Task;

use Im050\WeChat\Component\Console;
use Im050\WeChat\Component\Utils;
use Im050\WeChat\Task\Job\Job;

class TaskQueue
{

    /**
     * 配置
     *
     * @var array
     */
    public $config = [
        'max_process_num' => 10
    ];

    /**
     * 任务进程数
     *
     * @var int|mixed
     */
    public $process_num = 0;

    /**
     * 进程池
     *
     * @var array
     */
    public $process_pool = [];

    /**
     * TaskQueue constructor.
     *
     * @param array $config
     */
    public function __construct($config = array())
    {
        $this->config = array_merge($this->config, $config);
        $this->process_num = $this->config['max_process_num'];
    }

    /**
     * 创建任务进程
     */
    public function createProcess()
    {
        for ($i = 0; $i < $this->process_num; $i++) {
            $process = new \swoole_process(array($this, "onTask"));
            $process->useQueue();
            $pid = $process->start();
            $this->process_pool[$pid] = &$process;
        }
    }

    /**
     * 任务事件
     *
     * @param \swoole_process $worker
     * @throws \Exception
     */
    public function onTask(\swoole_process $worker)
    {
        while (($data = $worker->pop()) !== false) {
            $data = Utils::json_decode($data);
            $job = $data['job'];
            $params = $data['params'];
            $class = trim($job, "\\");

            if (!class_exists($class)) {
                $class = __NAMESPACE__ . '\\Job\\' . $job;
            }

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

    /**
     * 执行任务
     *
     * @param $job
     * @param $params
     */
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
        $data = Utils::json_encode($data);
        $process->push($data);
    }

    /**
     * 执行任务
     *
     * @param $job
     * @param $params
     */
    public static function run($job, $params)
    {
        if (app()->hasInstance('task_queue')) {
            app()->get('task_queue')->task($job, $params);
        } else {
            Console::log("尚未创建任务队列", Console::ERROR);
        }
    }

    /**
     * 关闭任务进程
     */
    public static function shutdown()
    {
        if (app()->hasInstance('task_queue')) {
            $task_queue = app()->get('task_queue');
        } else {
            Console::log("尚未创建任务队列", Console::ERROR);
        }
        reset($task_queue->process_pool);
        foreach($task_queue->process_pool as $pid => $process) {
            posix_kill($pid, SIGTERM);
        }
    }


}