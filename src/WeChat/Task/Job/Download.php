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
        $msgId = $this->params['msg_id'];
        $flag = false;
        switch($this->type) {
            case 'image':
                $flag = FileSystem::saveImage($msgId);
                break;
            case 'video':
                $flag = FileSystem::saveVideo($msgId);
                break;
            case 'voice':
                $flag = FileSystem::saveVoice($msgId);
                break;
            case 'emoticon':
                $flag = FileSystem::saveEmoticon($msgId);
                break;
            default:
                break;
        }
        if ($flag) {
            Console::log("下载 [$msgId] 资源完成");
        } else {
            Console::log("下载 [$msgId] 资源失败, 资源地址:" . http()->getQueryURI());
        }
    }
}