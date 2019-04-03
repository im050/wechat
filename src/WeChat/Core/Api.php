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
    public $baseRequest = [];

    /**
     * 授权信息获取uri
     *
     * @var string
     */
    public $redirectURI = '';


    /**
     * 基于swoole_atomic
     *
     * @var null
     */
    public $mediaCount = null;

    public static $uri = [
        'base_uri' => 'https://wx.qq.com/cgi-bin/mmwebwx-bin',
        'push_uri' => 'https://webpush.wx.qq.com/cgi-bin/mmwebwx-bin',
        'file_uri' => 'https://file.wx.qq.com/cgi-bin/mmwebwx-bin'
    ];

    public function __construct()
    {
        $this->mediaCount = new \swoole_atomic(0);
    }

    public static function uri($type)
    {
        return isset(self::$uri[$type]) ? self::$uri[$type] : '';
    }

    /**
     * 获取 MediaCount
     *
     * @return mixed
     */
    public function getMediaCount()
    {
        return $this->mediaCount->get();
    }

    /**
     * 增加 MediaCount
     */
    protected function addMediaCount()
    {
        $this->mediaCount->add();
    }

    /**
     * 减少 MediaCount
     */
    protected function subMediaCount()
    {
        $this->mediaCount->sub();
    }

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
        $deviceId = app()->auth->device_id;

        $payload = [
            'r'        => Utils::timeStamp(),
            '_'        => Utils::timeStamp(),
            'skey'     => $skey,
            'sid'      => $sid,
            'uin'      => $uin,
            'deviceid' => $deviceId,
            'synckey'  => SyncKey::getInstance()->string(),
        ];

        $url = uri('push_uri') . '/synccheck';
        $content = http()->get($url, $payload);
        $this->debug($content);
        preg_match('/window.synccheck=\{retcode:"(\d+)",selector:"(\d+)"\}/', $content, $matches);

        if (isset($matches[1]) && isset($matches[2])) {
            return [$matches[1], $matches[2]];
        }

        return [-1, -1];
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
            'BaseRequest' => $this->baseRequest,
            'SyncKey'     => [
                'Count' => SyncKey::getInstance()->count(),
                'List'  => SyncKey::getInstance()->get()
            ],
            'rr'          => ~time()
        ];

        $queryString = [
            'sid'         => app()->auth->sid,
            'skey'        => app()->auth->skey,
            'pass_ticket' => app()->auth->pass_ticket
        ];

        $url = uri('base_uri') . '/webwxsync?' . http_build_query($queryString);
        $content = http()->post($url, Utils::json_encode($payload));

        $this->debug($content);

        $data = Utils::json_decode($content);

        if (!checkBaseResponse($data)) {
            throw new \Exception("同步获取消息数据失败");
        }

        $syncKey = $data['SyncKey']['List'];
        SyncKey::getInstance()->setSyncKey($syncKey);

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
        $queryString = http_build_query([
            'pass_ticket' => $auth->pass_ticket,
            'skey'        => $auth->skey,
            'r'           => Utils::timeStamp()
        ]);
        $url = uri('base_uri') . '/webwxgetcontact?' . $queryString;
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
        $url = uri("base_uri") . '/webwxsendmsg?pass_ticket=' . app()->auth->pass_ticket;
        $msgId = (time() * 1000) . substr(uniqid(), 0, 5);
        $payload = [
            'BaseRequest' => $this->baseRequest,
            'Msg'         => [
                "Type"         => 1,
                "Content"      => $text,
                "FromUserName" => Account::username(),
                "ToUserName"   => $username,
                "LocalID"      => $msgId,
                "ClientMsgId"  => $msgId
            ]
        ];
        $data = http()->post($url, Utils::json_encode($payload));

        $this->debug($data);

        $data = Utils::json_decode($data);
        $flag = checkBaseResponse($data);
        return $flag;
    }

    protected function getMessageResource($type, $msgId)
    {
        if (!in_array($type, ['image', 'voice', 'video'])) {
            return false;
        }
        if (empty($msgId)) {
            return false;
        }
        $path = [
            'image' => 'webwxgetmsgimg',
            'voice' => 'webwxgetvoice',
            'video' => 'webwxgetvideo',
        ];
        $url = uri('base_uri') . '/' . $path[$type] . '?' . http_build_query([
                'MsgID' => $msgId,
                'skey'  => app()->auth->skey
            ]);

        if ($type == 'video') {
            $httpConfig = [
                'timeout' => 300,
                'headers' => [
                    'Range: bytes=0-'
                ]
            ];
        } else {
            $httpConfig = [
                'timeout' => 60,
            ];
        }

        try {
            $data = http()->get($url, [], $httpConfig);
        } catch (\Exception $e) {
            Console::log("下载 [{$msgId}] 资源超时, 类型： {$type}", Console::WARNING);
            return null;
        }
        return $data;
    }

    /**
     * 获取图片数据
     *
     * @param $msgId
     * @return array|mixed
     */
    public function getMessageImage($msgId)
    {
        return $this->getMessageResource('image', $msgId);
    }

    /**
     * 获取语音
     *
     * @param $msgId
     * @return array|bool|mixed
     */
    public function getMessageVoice($msgId)
    {
        return $this->getMessageResource('voice', $msgId);
    }

    /**
     * 获取视频
     *
     * @param $msgId
     * @return array|bool|mixed
     */
    public function getMessageVideo($msgId)
    {
        return $this->getMessageResource('video', $msgId);
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
            'BaseRequest'  => $this->baseRequest,
            'ClientMsgId'  => Utils::timeStamp(),
            'Code'         => 3,
            'FromUserName' => $username,
            'ToUserName'   => $username
        ];
        $queryString = [
            'lang'        => 'zh_CN',
            'pass_ticket' => app()->auth->pass_ticket
        ];
        $url = uri('base_uri') . "/webwxstatusnotify?" . http_build_query($queryString);

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
     * @param string $passTicket
     * @return mixed
     */
    public function webWxInit($uin, $sid, $skey = '', $passTicket = '')
    {
        $baseRequest = [
            'Uin'      => $uin,
            'Sid'      => $sid,
            'Skey'     => $skey,
            'DeviceID' => Utils::generateDeviceID(),
        ];

        $queryString = [
            'r'           => Utils::timeStamp(),
            'pass_ticket' => $passTicket
        ];

        $params = Utils::json_encode(['BaseRequest' => $baseRequest]);
        $url = uri('base_uri') . '/webwxinit?' . http_build_query($queryString);
        $content = http()->post($url, $params);

        $this->debug($content);

        $baseResponse = Utils::json_decode($content);

        if (checkBaseResponse($baseResponse)) {
            $this->baseRequest = [
                'Uin'      => app()->keymap->get('uin'),
                'Sid'      => app()->keymap->get('sid'),
                'Skey'     => app()->keymap->get('skey'),
                'DeviceID' => Utils::generateDeviceID()
            ];
        }

        return $baseResponse;
    }

    /**
     * 获取UUID
     *
     * @return bool
     */
    public function getUuid()
    {
        $url = 'https://login.wx.qq.com/jslogin';
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

        $url = 'https://login.wx.qq.com/cgi-bin/mmwebwx-bin/login';
        $payload = [
            'uuid' => app()->auth->uuid,
            'tip'  => 1,
            '_'    => Utils::timeStamp()
        ];

        $content = http()->get($url, $payload, [
            'timeout' => 35
        ]);

        $this->debug($content);

        $code = -1;

        preg_match('/window.code=(\d+);/', $content, $matches);
        if (isset($matches[1])) {
            $code = $matches[1];
        }

        preg_match('/window.redirect_uri="(\S+?)";/', $content, $matches);

        if (isset($matches[1])) {
            $this->redirectURI = $matches[1];
            //获取主机地址
            $urlParser = parse_url($this->redirectURI);
            $host = $urlParser['host'];
            //记录uri的host
            app()->keymap->set('uri_host', $host)->save();
            //变更uri的host
            $this->modifyUri($host);
        }


        return $code;
    }

    /**
     * 修改接口主机名
     *
     * @param $host
     */
    public function modifyUri($host)
    {
        $url = 'https://%s/cgi-bin/mmwebwx-bin';
        //替换默认域名
        self::$uri['file_uri'] = sprintf($url, 'file.' . $host);
        self::$uri['push_uri'] = sprintf($url, 'webpush.' . $host);
        self::$uri['base_uri'] = sprintf($url, $host);
    }

    /**
     * 获取权限信息
     *
     * @return array
     * @throws \Exception
     */
    public function getToken()
    {

        if (empty($this->redirectURI)) {
            throw new \Exception("获取权限验证数据失败");
        }

        $payload = array(
            'uuid'    => app()->auth->uuid,
            'fun'     => 'new',
            'version' => 'v2'
        );

        $content = http()->get($this->redirectURI, $payload);

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

        $url = uri('base_uri') . '/webwxbatchgetcontact?' . http_build_query([
                'type'        => 'ex',
                'pass_ticket' => app()->auth->pass_ticket,
                'r'           => Utils::timeStamp()
            ]);

        $list = [];

        foreach ($users as $username) {
            $list[] = ["UserName" => $username, "EncryChatRoomId" => ""];
        }

        $payload = [
            'BaseRequest' => $this->baseRequest,
            "Count"       => count($users),
            "List"        => $list
        ];

        $content = http()->post($url, Utils::json_encode($payload));

        $this->debug($content);

        $content = Utils::json_decode($content);

        return $content;
    }

    /**
     * 上传文件接口
     *
     * @param $username
     * @param $file
     * @return bool|mixed
     */
    public function uploadMedia($username, $file)
    {

        if (!file_exists($file)) {
            Console::log("上传文件 {$file} 不存在!", Console::WARNING);
            return false;
        }

        $url = uri('file_uri') . '/webwxuploadmedia?f=json';

        list($mime, $mediaType) = $this->getMediaType($file);

        $data = [
            'id'                 => 'WU_FILE_' . $this->getMediaCount(),
            'name'               => basename($file),
            'type'               => $mime,
            'lastModifieDate'    => gmdate('D M d Y H:i:s TO', filemtime($file)) . ' (CST)',
            'size'               => filesize($file),
            'mediatype'          => $mediaType,
            'uploadmediarequest' => Utils::json_encode([
                'BaseRequest'   => $this->baseRequest,
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
            'webwx_data_ticket'  => $this->getDataTicket(),
            'pass_ticket'        => app()->auth->pass_ticket,
            'filename'           => new \CURLFile($file),
        ];

        try {
            $content = http()->post($url, $data, [
                'timeout' => 120
            ]);
        } catch (\Exception $e) {
            Console::log("上传文件出现错误, Error:" . $e->getMessage(), Console::WARNING);
            return false;
        }

        $this->debug($content);

        $content = Utils::json_decode($content);

        if (!checkBaseResponse($content)) {
            return false;
        }

        $this->addMediaCount();

        return $content;
    }


    /**
     * 发送文件接口
     *
     * @param $username
     * @param $file
     * @return bool
     */
    public function sendFile($username, $file)
    {
        $response = $this->uploadMedia($username, $file);

        if (!$response) {
            return false;
        }

        $mediaId = $response['MediaId'];
        $msgId = (time() * 1000) . substr(uniqid(), 0, 5);
        $url = uri('base_uri') . '/webwxsendappmsg?fun=async&f=json&pass_ticket=' . app()->auth->pass_ticket;
        $payload = [
            'BaseRequest' => $this->baseRequest,
            'Msg'         => [
                'Type'         => 6,
                'Content'      => sprintf("<appmsg appid='wxeb7ec651dd0aefa9' sdkver=''><title>%s</title><des></des><action></action><type>6</type><content></content><url></url><lowurl></lowurl><appattach><totallen>%s</totallen><attachid>%s</attachid><fileext>%s</fileext></appattach><extinfo></extinfo></appmsg>", basename($file), filesize($file), $mediaId, end(explode('.', $file))),
                'FromUserName' => Account::username(),
                'ToUserName'   => $username,
                'LocalID'      => $msgId,
                'ClientMsgId'  => $msgId
            ]
        ];

        $result = http()->post($url, Utils::json_encode($payload));

        $this->debug($result);

        if (!checkBaseResponse(Utils::json_decode($result))) {
            Console::log("发送文件失败, File: " . $file, Console::WARNING);
            return false;
        }

        return true;
    }

    /**
     * 发送图片
     *
     * @param $username
     * @param $file
     * @return bool
     */
    public function sendImage($username, $file)
    {
        $response = $this->uploadMedia($username, $file);

        if (!$response) {
            return false;
        }

        $mediaId = $response['MediaId'];
        $msgId = (time() * 1000) . substr(uniqid(), 0, 5);
        $url = uri('base_uri') . '/webwxsendmsgimg?fun=async&f=json&pass_ticket=' . app()->auth->pass_ticket;
        $payload = [
            'BaseRequest' => $this->baseRequest,
            'Msg'         => [
                'Type'         => 3,
                'Content'      => '',
                'MediaId'      => $mediaId,
                'FromUserName' => Account::username(),
                'ToUserName'   => $username,
                'LocalID'      => $msgId,
                'ClientMsgId'  => $msgId
            ],
            'Scene'       => 0
        ];

        $result = http()->post($url, Utils::json_encode($payload));

        $this->debug($result);

        if (!checkBaseResponse(Utils::json_decode($result))) {
            Console::log("发送图片失败, File: " . $file, Console::WARNING);
            return false;
        }

        return true;
    }

    /**
     * 发送表情
     *
     * @param $username
     * @param $file
     * @return bool
     */
    public function sendEmoticon($username, $file)
    {
        $response = $this->uploadMedia($username, $file);

        if (!$response) {
            return false;
        }

        $mediaId = $response['MediaId'];
        $msgId = (time() * 1000) . substr(uniqid(), 0, 5);
        $url = uri('base_uri') . '/webwxsendemoticon?fun=sys&f=json&pass_ticket=' . app()->auth->pass_ticket;
        $payload = [
            'BaseRequest' => $this->baseRequest,
            'Msg'         => [
                'Type'         => 47,
                "EmojiFlag"    => 2,
                'MediaId'      => $mediaId,
                'FromUserName' => Account::username(),
                'ToUserName'   => $username,
                'LocalID'      => $msgId,
                'ClientMsgId'  => $msgId
            ]
        ];

        $result = http()->post($url, Utils::json_encode($payload));

        $this->debug($result);

        if (!checkBaseResponse(Utils::json_decode($result))) {
            Console::log("发送表情失败, File: " . $file, Console::WARNING);
            return false;
        }

        return true;
    }

    /**
     * 获取文件类型
     *
     * @param $file
     * @return array
     */
    public function getMediaType($file)
    {
        $info = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($info, $file);
        finfo_close($info);

        if (stripos($mime, "image") !== false) {
            $mediaType = 'pic';
        } else {
            $mediaType = 'doc';
        }
        return [$mime, $mediaType];
    }

    /**
     * 从Cookie中获取webwx_data_ticket
     *
     * @return bool
     */
    public function getDataTicket()
    {
        static $ticket = false;
        if ($ticket != false) {
            return $ticket;
        }
        $cookiePath = config('cookiefile_path');
        if (file_exists($cookiePath)) {
            $fp = fopen($cookiePath, 'r');
            while ($line = fgets($fp)) {
                if (strpos($line, 'webwx_data_ticket') !== false) {
                    $metaData = explode("\t", trim($line));
                    $ticket = $metaData[6];
                    break;
                }
            }
            fclose($fp);
        }

        return $ticket;
    }

    /**
     * 调试日志
     *
     * @param $data
     * @return bool|int
     */
    public function debug($data)
    {
        if (!config('api_debug')) {
            return false;
        }
        $log = [
            '代码追踪' => Utils::json_encode(debug_backtrace()),
            '消息数据' => is_array($data) ? Utils::json_encode($data) : $data,
            '日志时间' => Utils::now()
        ];
        $path = config('api_debug_log_path');
        return Logger::write($log, $path);
    }

}