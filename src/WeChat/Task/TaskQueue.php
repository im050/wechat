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
use Swoole\Process;

class TaskQueue
{

    /**
     * 配置
     *
     * @var array
     */
    public $config = [
        'max_process_num' => 1
    ];

    /**
     * 任务进程数
     *
     * @var int|mixed
     */
    public $processNum = 0;

    /**
     * 进程池
     *
     * @var array
     */
    public $processPool = [];

    /**
     * TaskQueue constructor.
     *
     * @param array $config
     */
    public function __construct($config = array())
    {
        $this->config = array_merge($this->config, $config);
        $this->processNum = $this->config['max_process_num'];
    }

    /**
     * 创建任务进程
     */
    public function createProcess()
    {
        for ($i = 0; $i < $this->processNum; $i++) {
            $process = new Process(array($this, "onTask"));
            $process->useQueue();
            $pid = $process->start();
            $this->processPool[$pid] = &$process;
        }
    }

    /**
     * 任务事件
     *
     * @param Process $worker
     * @throws \Exception
     */
    public function onTask(Process $worker)
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
        if (count($this->processPool) <= 0) {
            $this->createProcess();
        }
        $process = current($this->processPool);
        $data = array(
            'job'    => $job,
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
        if (app()->hasInstance('taskQueue')) {
            app()->taskQueue->task($job, $params);
        } else {
            Console::log("尚未创建任务队列", Console::ERROR);
        }
    }

    /**
     * 关闭任务进程
     */
    public static function shutdown()
    {
        if (app()->hasInstance('taskQueue')) {
            $taskQueue = app()->taskQueue;
            if (isset($taskQueue) && $taskQueue instanceof TaskQueue) {
                reset($taskQueue->processPool);
                foreach ($taskQueue->processPool as $pid => $process) {
                    posix_kill($pid, SIGTERM);
                }
            }
        } else {
            Console::log("尚未创建任务队列", Console::ERROR);
        }
    }


}