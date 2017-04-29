<?php
/**
 * Created by PhpStorm.
 * User: linyulin
 * Date: 17/4/29
 * Time: 下午6:47
 */

namespace Im050\WeChat\Task\Job;

use Im050\WeChat\Core\FileSystem;

class Download extends Job
{

    public function run() {
        $msg_id = $this->msg_id;
        switch($this->type) {
            case 'image':
                FileSystem::saveImage($msg_id);
                break;
            case 'video':
                break;
            case 'voice':
                break;
            default:
                break;
        }
    }

}