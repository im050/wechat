<?php
/**
 * Created by PhpStorm.
 * User: linyulin
 * Date: 2019/4/9
 * Time: 2:27 PM
 */

namespace Im050\WeChat\Crontab;

use Im050\WeChat\Crontab\Parser\CrontabParse;
use Swoole\Process;

/**
 * Class Crontab
 *
 * @package Im050\WeChat\Crontab
 */
class Crontab
{
    private $missions = [];

    private $cronProcess;

    private $runtimeRecords = [];

    public function register($cronString, callable $callback): Crontab
    {
        $mission = new Mission();
        $mission->setCronString($cronString)
            ->setCallback($callback);
        array_push($this->missions, $mission);
        return $this;
    }

    public function start()
    {
        if (empty($this->missions)) {
            return ;
        }
        $parentPid = posix_getpid();
        $this->cronProcess = new Process(function () use ($parentPid) {
            while (true) {
                $ppid = posix_getppid();
                if ($ppid != $parentPid) {
                    break;
                }
                $time = time();
                /** @var Mission $mission */
                foreach ($this->missions as $key => $mission) {
                    $time = CrontabParse::parse($mission->getCronString(), $time);
                    $nextRunDate = date("Y-m-d H:i", $time);
                    $lastRunDate = array_key_exists($key, $this->runtimeRecords) ? $this->runtimeRecords[$key] : "0000-00-00 00:00";
                    if (date("Y-m-d H:i", time()) == $nextRunDate && $lastRunDate != $nextRunDate) {
                        $this->runtimeRecords[$key] = $nextRunDate;
                        $forkProcess = new Process(function() use($mission) {
                            call_user_func($mission->getCallback());
                            exit(0);
                        });
                        $forkProcess->start();
                    }
                }
                sleep(10);
            }
            Process::wait();
            exit(0);
        });
        $this->cronProcess->start();
    }
}