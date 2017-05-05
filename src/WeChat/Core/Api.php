<?php
namespace Im050\WeChat\Core;

use Im050\WeChat\Component\Console;
use Im050\WeChat\Component\Logger;
use Im050\WeChat\Component\Utils;

class Api
{

    /**
     * 基础请求信息
     *
     * @var array
     */
    public $base_request = [];

    /**
     * 授权信息获取uri
     *
     * @var string
     */
    public $redirect_uri = '';

    /**
     * 同步检测
     *
     * @return array
     */
    public function syncCheck()
    {

        $sid = app()->auth->sid;
        $skey = app()->auth->skey;
        $uin = app()->auth->uin;
        $device_id = app()->auth->device_id;

        $payload = [
            'r'        => Utils::timeStamp(),
            '_'        => Utils::timeStamp(),
            'skey'     => $skey,
            'sid'      => $sid,
            'uin'      => $uin,
            'deviceid' => $device_id,
            'synckey'  => SyncKey::getInstance()->string(),
        ];

        $url = uri('push_uri') . '/cgi-bin/mmwebwx-bin/synccheck';
        $content = http()->get($url, $payload);
        $this->debug($content);
        preg_match('/window.synccheck=\{retcode:"(\d+)",selector:"(\d+)"\}/', $content, $matches);
        return [$matches[1], $matches[2]];
    }

    /**
     * 拉取消息
     *
     * @return mixed
     * @throws \Exception
     */
    public function pullMessage()
    {
        $payload = [
            'BaseRequest' => $this->base_request,
            'SyncKey'     => [
                'Count' => SyncKey::getInstance()->count(),
                'List'  => SyncKey::getInstance()->get()
            ],
            'rr'          => Utils::timeStamp()
        ];

        $query_string = [
            'sid'         => app()->auth->sid,
            'skey'        => app()->auth->skey,
            'pass_ticket' => app()->auth->pass_ticket
        ];

        $url = uri('base_uri') . '/cgi-bin/mmwebwx-bin/webwxsync?' . http_build_query($query_string);
        $content = http()->post($url, Utils::json_encode($payload));

        $this->debug($content);

        $data = Utils::json_decode($content);

        if (!checkBaseResponse($data)) {
            throw new \Exception("同步获取消息数据失败");
        }

        $sync_key = $data['SyncKey']['List'];
        SyncKey::getInstance()->setSyncKey($sync_key);

        return $data;
    }

    /**
     * 获取通讯录
     *
     * @return mixed
     * @throws \Exception
     */
    public function getContact()
    {
        $auth = app()->auth;
        $query_string = http_build_query([
            'pass_ticket' => $auth->pass_ticket,
            'skey'        => $auth->skey,
            'r'           => Utils::timeStamp()
        ]);
        $url = uri('base_uri') . '/cgi-bin/mmwebwx-bin/webwxgetcontact?' . $query_string;
        $content = http()->post($url);

        $this->debug($content);

        $data = Utils::json_decode($content);

        if (!checkBaseResponse($data)) {
            throw new \Exception("获取联系人失败");
        }

        return $data;
    }

    /**
     * 发送消息
     *
     * @param $text
     * @param $username
     * @return bool
     */
    public function sendMessage($username, $text)
    {
        $url = uri("base_uri") . '/cgi-bin/mmwebwx-bin/webwxsendmsg?pass_ticket=' . app()->auth->pass_ticket;
        $msg_id = (time() * 1000) . substr(uniqid(), 0, 5);
        $payload = [
            'BaseRequest' => $this->base_request,
            'Msg'         => [
                "Type"         => 1,
                "Content"      => $text,
                "FromUserName" => Account::username(),
                "ToUserName"   => $username,
                "LocalID"      => $msg_id,
                "ClientMsgId"  => $msg_id
            ]
        ];
        $data = http()->post($url, Utils::json_encode($payload));

        $this->debug($data);

        $data = Utils::json_decode($data);
        $flag = checkBaseResponse($data);
        return $flag;
    }

    protected function getMessageResource($type, $msg_id)
    {
        if (!in_array($type, ['image', 'voice', 'video'])) {
            return false;
        }
        if (empty($msg_id)) {
            return false;
        }
        $path = [
            'image' => 'webwxgetmsgimg',
            'voice' => 'webwxgetvoice',
            'video' => 'webwxgetvideo',
        ];
        $url = uri('base_uri') . '/cgi-bin/mmwebwx-bin/' . $path[$type] . '?' . http_build_query([
                'MsgID' => $msg_id,
                'skey'  => app()->auth->skey
            ]);

        if ($type == 'video') {
            $http_config = [
                'timeout' => 300,
                'headers' => [
                    'Range: bytes=0-'
                ]
            ];
        } else {
            $http_config = [
                'timeout' => 60,
            ];
        }

        try {
            $data = http()->get($url, [], $http_config);
        } catch (\Exception $e) {
            if (config('debug')) {
                $path = config('exception_log_path');
                Logger::write($e, $path);
            }
            Console::log("下载资源超时", Console::WARNING);
            return null;
        }
        return $data;
    }

    /**
     * 获取图片数据
     *
     * @param $msg_id
     * @return array|mixed
     */
    public function getMessageImage($msg_id)
    {
        return $this->getMessageResource('image', $msg_id);
    }

    /**
     * 获取语音
     *
     * @param $msg_id
     * @return array|bool|mixed
     */
    public function getMessageVoice($msg_id)
    {
        return $this->getMessageResource('voice', $msg_id);
    }

    /**
     * 获取视频
     *
     * @param $msg_id
     * @return array|bool|mixed
     */
    public function getMessageVideo($msg_id)
    {
        return $this->getMessageResource('video', $msg_id);
    }

    /**
     * 微信状态通知
     *
     * @param string $username
     * @return bool
     */
    public function statusNotify($username = '')
    {
        if (empty($username)) {
            $username = Account::username();
        }

        $payload = [
            'BaseRequest'  => $this->base_request,
            'ClientMsgId'  => Utils::timeStamp(),
            'Code'         => 3,
            'FromUserName' => $username,
            'ToUserName'   => $username
        ];
        $query_string = [
            'lang'        => 'zh_CN',
            'pass_ticket' => app()->auth->pass_ticket
        ];
        $url = uri('base_uri') . "/cgi-bin/mmwebwx-bin/webwxstatusnotify?" . http_build_query($query_string);

        $content = http()->post($url, Utils::json_encode($payload));

        $this->debug($content);

        $data = Utils::json_decode($content);

        if (checkBaseResponse($data)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 初始化微信
     *
     * @param $uin
     * @param $sid
     * @param string $skey
     * @return mixed
     */
    public function webWxInit($uin, $sid, $skey = '', $pass_ticket = '')
    {
        $base_request = [
            'Uin'      => $uin,
            'Sid'      => $sid,
            'Skey'     => $skey,
            'DeviceID' => Utils::generateDeviceID(),
        ];

        $query_string = [
            'r'           => Utils::timeStamp(),
            'pass_ticket' => $pass_ticket
        ];

        $params = Utils::json_encode(['BaseRequest' => $base_request]);
        $url = uri('base_uri') . '/cgi-bin/mmwebwx-bin/webwxinit?' . http_build_query($query_string);
        $content = http()->post($url, $params);

        $this->debug($content);

        $base_response = Utils::json_decode($content);

        if (checkBaseResponse($base_response)) {
            $this->base_request = $base_request;
        }

        return $base_response;
    }

    /**
     * 获取UUID
     *
     * @return bool
     */
    public function getUuid()
    {
        $url = uri('login_uri') . '/jslogin';
        $payload = [
            'appid' => 'wx782c26e4c19acffb',
            'fun'   => 'new',
            '_'     => round(microtime(true) * 1000)
        ];
        $content = http()->get($url, $payload);

        $this->debug($content);

        $pattern = '/window.QRLogin.code = (\d+); window.QRLogin.uuid = "(\S+?)"/';

        if (preg_match($pattern, $content, $matches)) {
            $code = $matches[1];
            $uuid = $matches[2];
        } else {
            return false;
        }

        if ($code == '200') {
            return $uuid;
        } else {
            return false;
        }
    }

    /**
     * 获取登录状态
     *
     * @return int
     * @throws \Exception
     */
    public function getLoginStatus()
    {
        if (empty(app()->auth->uuid)) {
            throw new \Exception("缺少UUID");
        }

        $url = uri('login_uri') . '/cgi-bin/mmwebwx-bin/login';
        $payload = [
            'uuid' => app()->auth->uuid,
            'tip'  => 1,
            '_'    => Utils::timeStamp()
        ];

        $content = http()->get($url, $payload, [
            'timeout' => 35
        ]);

        $code = -1;

        preg_match('/window.code=(\d+);/', $content, $matches);
        if (isset($matches[1])) {
            $code = $matches[1];
        }

        preg_match('/window.redirect_uri="(\S+?)";/', $content, $matches);

        if (isset($matches[1])) {
            $this->redirect_uri = $matches[1];
        }


        return $code;
    }

    /**
     * 获取权限信息
     *
     * @return array
     * @throws \Exception
     */
    public function getToken()
    {

        if (empty($this->redirect_uri)) {
            throw new \Exception("获取权限验证数据失败");
        }

        $payload = array(
            'uuid'    => app()->auth->uuid,
            'fun'     => 'new',
            'version' => 'v2'
        );

        $content = http()->get($this->redirect_uri, $payload);

        $this->debug($content);

        $data = Utils::xmlToArray($content);
        if (intval($data['ret']) != 0) {
            throw new \Exception("获取通行证失败");
        }

        $result = [
            'sid'         => $data['wxsid'],
            'skey'        => $data['skey'],
            'uin'         => $data['wxuin'],
            'pass_ticket' => $data['pass_ticket']
        ];

        return $result;
    }

    /**
     * 批量获取用户资料
     *
     * @param $users
     * @return mixed
     */
    public function getBatchContact($users)
    {

        if (is_string($users)) {
            $users = (array)$users;
        }

        $url = uri('base_uri') . '/cgi-bin/mmwebwx-bin/webwxbatchgetcontact?' . http_build_query([
                'type'        => 'ex',
                'pass_ticket' => app()->auth->pass_ticket,
                'r'           => Utils::timeStamp()
            ]);

        $list = [];

        foreach ($users as $username) {
            $list[] = ["UserName" => $username, "EncryChatRoomId" => ""];
        }

        $payload = [
            'BaseRequest' => $this->base_request,
            "Count"       => count($users),
            "List"        => $list
        ];

        $content = http()->post($url, Utils::json_encode($payload));

        $this->debug($content);

        $content = Utils::json_decode($content);

        return $content;
    }

    public function uploadMedia($username, $file)
    {
        $data = [
            'id'                 => 'WU_FILE_0',
            'name'               => basename($file),
            'type'               => 'image/jpeg',
            'lastModifieDate'    => gmdate('D M d Y H:i:s TO', filemtime($file)) . ' (CST)',
            'size'               => filesize($file),
            'mediatype'          => 'pic',
            'uploadmediarequest' => Utils::json_encode([
                'BaseRequest'   => $this->base_request,
                'ClientMediaId' => time(),
                'TotalLen'      => filesize($file),
                'StartPos'      => 0,
                'DataLen'       => filesize($file),
                'MediaType'     => 4,
                'UploadType'    => 2,
                'FromUserName'  => Account::username(),
                'ToUserName'    => $username,
                'FileMd5'       => md5_file($file)
            ]),
            'webwx_data_ticket'  => '123456',
            'pass_ticket'        => '654321',
            'filename'           => new CURLFile($file),
        ];
    }

    public function debug($data)
    {
        if (!config('api_debug')) {
            return false;
        }
        $log = [
            '代码位置' => __LINE__,
            '消息数据' => is_array($data) ? Utils::json_encode($data) : $data,
            '日志时间' => Utils::now()
        ];
        $path = config('api_debug_log_path');
        return Logger::write($log, $path);
    }

}