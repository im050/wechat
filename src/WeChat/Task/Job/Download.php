<?php
/**
 * Created by PhpStorm.
 * User: linyulin
 * Date: 17/4/29
 * Time: 下午6:47
 */

namespace Im050\WeChat\Task\Job;

use Im050\WeChat\Component\Console;
use Im050\WeChat\Core\FileSystem;

class Download extends Job
{
    public function run() {
        $msg_id = $this->msg_id;
        $flag = false;
        switch($this->type) {
            case 'image':
                $flag = FileSystem::saveImage($msg_id);
                break;
            case 'video':
                $flag = FileSystem::saveVideo($msg_id);
                break;
            case 'voice':
                $flag = FileSystem::saveVoice($msg_id);
                break;
            default:
                break;
        }
        if ($flag) {
            Console::log("下载[$msg_id]资源完成");
        } else {
            Console::log("下载[$msg_id]资源失败, 资源地址:" . http()->getQueryURI());
        }
    }
}