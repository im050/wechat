<?php
namespace Im050\WeChat\Core;

use Im050\WeChat\Component\Console;
use Im050\WeChat\Component\Utils;
use PHPQRCode\QRcode;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Output\ConsoleOutput;

class Auth
{

    protected static $_instance = null;

    public $uuid = null;

    public $device_id = '';

    public $sid = '';

    public $skey = '';

    public $pass_ticket = '';

    public $uin = '';

    public $base_request = [];

    public $sync_key = [];

    const LOGIN_SUCCESS = 200;

    const LOGIN_TIMEOUT = 408;

    const LOGIN_CONFIRM = 201;

    private function __construct()
    {

    }

    public static function getInstance()
    {
        if (self::$_instance === null) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * 轮询登录状态
     *
     * @return int
     */
    public function pollingLogin()
    {
        for ($i = 0; $i < 10; $i++) {
            $data = $this->checkLogin();
            $code = intval($data['window_code']);
            switch ($code) {
                case self::LOGIN_SUCCESS:
                    Console::log('登录成功.');
                    $redirect_uri = $data['window_redirect_uri'];
                    $scan = $data['scan'];
                    $this->initAuthInfo($redirect_uri, $scan);
                    return $code;
                case self::LOGIN_CONFIRM:
                    if (!isset($click_btn)) {
                        $click_btn = true;
                        Console::log("请在手机上点击登录按钮.");
                    }
                    $i--;
                    sleep(1);
                    break;
                default:
                    return $code;
            }
        }

        Console::log("登录失败");
        exit;
    }

    /**
     * 获得二维码实际内容并打印到控制台
     */
    public function openQRcode()
    {
        $img = null;
        for ($i = 0; $i <= 10; $i++) {
            if (!$this->initLoginData()) {
                continue;
            }
            $img = $this->getQRcode();
            $this->generateQRcode($img);
            break;
        }
        if ($img === null) {
            Console::log("获取二维码失败");
            exit;
        }
    }

    /**
     * 初始化UUID
     *
     * @return bool
     */
    protected function initLoginData()
    {
        $url = uri('login_uri') . '/jslogin';
        $payload = [
            'appid' => 'wx782c26e4c19acffb',
            'fun' => 'new',
            '_' => round(microtime(true) * 1000)
        ];
        $content = http()->get($url, $payload);
        $data = $this->parseContent($content);
        $code = intval($data['window_QRLogin_code']);
        if ($code == 200) {
            $this->uuid = $data['window_QRLogin_uuid'];
        } else {
            return false;
        }
        return true;
    }

    /**
     * 初始化授权信息
     * 例如pass_ticket, skey, sid
     *
     * @param string $redirect_uri
     * @param string $scan
     */
    protected function initAuthInfo($redirect_uri = '', $scan = '')
    {
        if (empty($redirect_uri) || empty($scan)) {
            Console::log("获取授权验证数据错误", Console::ERROR);
        }
        $payload = array(
            'uuid' => $this->uuid,
            'scan' => $scan,
            'fun' => 'new',
            'version' => 'v2'
        );
        $content = http()->get($redirect_uri, $payload);
        $data = Utils::xmlToArray($content);
        if (intval($data['ret']) != 0) {
            Console::log("获取数据失败，错误码: " . $data['ret'], Console::ERROR);
        }
        $this->sid = $data['wxsid'];
        $this->skey = $data['skey'];
        $this->uin = $data['wxuin'];
        $this->pass_ticket = $data['pass_ticket'];
    }

    /**
     * 获取登录状态
     *
     * @return mixed
     */
    protected function checkLogin()
    {
        $url = uri('login_uri') . '/cgi-bin/mmwebwx-bin/login';
        $payload = [
            'uuid' => $this->uuid,
            'tip' => 1,
            '_' => Utils::timeStamp()
        ];
        $content = http()->get($url, $payload, [
            'timeout' => 35
        ]);
        $data = $this->parseContent($content);
        return $data;
    }

    /**
     * 实际的二维码内容
     *
     * @return string
     */
    public function getQRcode()
    {
        //二维码实际内容
        $img_url = 'https://login.weixin.qq.com/l/' . $this->uuid;
        return $img_url;
    }

    /**
     * 解析响应数据
     *
     * @param $content
     * @return mixed
     */
    public function parseContent($content)
    {
        $content = str_replace(array(" ", ";", "\"", "\r\n", "\n"), array("", "&", "", "", ""), $content);
        parse_str($content, $data);
        return $data;
    }

    /**
     * 微信状态通知
     *
     * @return bool
     */
    public function statusNotify()
    {
        $payload = [
            'BaseRequest' => [
                'DeviceID' => $this->device_id,
                'Sid' => $this->sid,
                'Skey' => $this->skey,
                'Uin' => $this->uin
            ],
            'ClientMsgId' => Utils::timeStamp(),
            'Code' => 3,
            'FromUserName' => Account::username(),
            'ToUserName' => Account::username()
        ];
        $query_string = [
            'lang' => 'zh_CN',
            'pass_ticket' => $this->pass_ticket
        ];
        $url = uri('base_uri') . "/cgi-bin/mmwebwx-bin/webwxstatusnotify?" . http_build_query($query_string);
        $content = http()->post($url, json_encode($payload));
        $data = json_decode($content, JSON_OBJECT_AS_ARRAY);
        if (checkBaseResponse($data)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 初始化微信
     */
    public function webWxInit()
    {
        $this->base_request = [
            'Uin' => $this->uin,
            'Sid' => $this->sid,
            'Skey' => $this->skey,
            'DeviceID' => $this->generateDeviceID()
        ];
        $query_string = [
            'r' => Utils::timeStamp(),
            'pass_ticket' => $this->pass_ticket
        ];
        $params = json_encode(['BaseRequest' => $this->base_request]);
        $url = uri('base_uri') . '/cgi-bin/mmwebwx-bin/webwxinit?' . http_build_query($query_string);
        $content = http()->post($url, $params);
        $base_response = json_decode($content, JSON_OBJECT_AS_ARRAY);
        if (!checkBaseResponse($base_response)) {
            Console::log("初始化数据失败，错误码：" . $base_response['BaseResponse']['Ret'], Console::ERROR);
        }
        $this->initAccount($base_response['User']);
        $this->initSyncKey($base_response['SyncKey']['List']);
    }

    /**
     * 初始化同步key
     *
     * @param $list
     */
    public function initSyncKey($list)
    {
        $sync_key = SyncKey::getInstance();
        $sync_key->setSyncKey($list);
    }

    /**
     * 初始化本地账号
     *
     * @param $user
     */
    public function initAccount($user)
    {
        $account = Account::getInstance();
        $account->nickname = $user['NickName'];
        $account->username = $user['UserName'];
        $account->sex = $user['Sex'];
        $account->uin = $user['Uin'];
    }

    /**
     * 生成设备ID
     *
     * @return string
     */
    public function generateDeviceID()
    {
        $this->device_id = 'e' . Utils::randomString(15, true);
        return $this->device_id;
    }

    /**
     * 打印二维码到控制台
     *
     * @param $text
     */
    public function generateQRcode($text)
    {
        $output = new ConsoleOutput();

        $style = new OutputFormatterStyle('black', 'black', array('bold'));
        $output->getFormatter()->setStyle('blackc', $style);

        $style = new OutputFormatterStyle('white', 'white', array('bold'));
        $output->getFormatter()->setStyle('whitec', $style);

        if (Utils::isWin()) {
            $pxMap = ['<whitec>mm</whitec>', '<blackc>  </blackc>'];
        } else {
            $pxMap = ['<whitec>  </whitec>', '<blackc>  </blackc>'];
        }

        $text = QRcode::text($text);
        $length = strlen($text[0]);

        foreach ($text as $line) {
            $output->write($pxMap[0]);
            for ($i = 0; $i < $length; $i++) {
                $type = substr($line, $i, 1);
                $output->write($pxMap[$type]);
            }
            $output->writeln($pxMap[0]);
        }
    }

}