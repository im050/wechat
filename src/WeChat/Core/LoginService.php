<?php
namespace Im050\WeChat\Core;

use Im050\WeChat\Collection\ContactFactory;
use Im050\WeChat\Collection\ContactPool;
use Im050\WeChat\Component\Console;
use Im050\WeChat\Component\Utils;
use PHPQRCode\QRcode;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Output\ConsoleOutput;

class LoginService
{

    const LOGIN_SUCCESS = 200;

    const LOGIN_TIMEOUT = 408;

    const LOGIN_CONFIRM = 201;

    public $init_response = [];

    /**
     * 扫码登录
     *
     * @return boolean
     */
    public function scanLogin()
    {
        Console::log("正在为您准备二维码...");

        do {
            //打印二维码
            $this->openQRcode();

            Console::log("请扫描二维码");

            //轮询登录状态
            $flag = $this->pollingLogin();

            if ($flag === false) {
                Console::log("登录失败，请重新运行本程序", Console::ERROR);
            }

            if ($flag == LoginService::LOGIN_TIMEOUT) {
                Console::log("扫码二维码超时，正在为您重新生成二维码...");
                continue;
            }

            if ($flag != LoginService::LOGIN_SUCCESS) {
                Console::log("程序运行异常，请重新启动", Console::ERROR);
            }

            Console::log("正在初始化账号数据...");

            $uin = app()->auth->uin;
            $sid = app()->auth->sid;
            $skey = app()->auth->skey;
            $pass_ticket = app()->auth->pass_ticket;

            $response = app()->api->webWxInit($uin, $sid, $skey, $pass_ticket);

            $this->init_response = $response;

            if (!checkBaseResponse($response)) {
                Console::log("初始化失败，请重新运行本程序", Console::ERROR);
            } else {
                Account::getInstance()->setUser($response['User']);
                SyncKey::getInstance()->setSyncKey($response['SyncKey']['List']);
                return true;
            }

        } while (true);

        return false;
    }


    /**
     * 尝试登录
     * 用上一次登录的缓存数据进行登录
     *
     * @return bool
     */
    public function tryLogin()
    {

        Console::log("正在尝试免扫码登录...");

        //加载上一次登录的用户数据
        app()->auth->loadTokenFromCache();

        if (empty(app()->auth->uin) || empty(app()->auth->sid)) {
            return false;
        }

        //尝试初始化
        $response = app()->api->webWxInit('xuin=' . app()->auth->uin, app()->auth->sid);

        $this->init_response = $response;

        if (!checkBaseResponse($response)) {
            return false;
        } else {
            //初始化成功更新数据
            Account::getInstance()->setUser($response['User']);
            SyncKey::getInstance()->setSyncKey($response['SyncKey']['List']);
            //更新skey和登录时间
            app()->auth->skey = isset($response['SKey']) ? $response['SKey'] : app()->auth->skey;
            app()->keymap->setMultiple([
                'skey'       => app()->auth->skey,
                'login_time' => time()
            ])->save();
            return true;
        }
    }

    /**
     * 开始登录逻辑
     *
     * @return bool
     */
    public function start()
    {
        $last_login_time = app()->keymap->get('login_time');

        $status = false;

        if (time() - $last_login_time <= 300) {
            $status = $this->tryLogin();
        }

        if (!$status) {
            $status = $this->scanLogin();
        }

        if ($status) {
            //登录成功操作
            $this->prepare();

            Console::log("欢迎您，" . Account::nickname());
        }

        return $status;
    }

    public function prepare()
    {
        Console::log("关闭手机通知状态...");

        app()->api->statusNotify();

        Console::log("正在初始化联系人...");

        $contact_pool = ContactPool::getInstance();

        $data = [];

        try {
            $data = app()->api->getContact();
        } catch (\Exception $e) {
            Console::log($e->getMessage(), Console::ERROR);
        }

        $member_list = $data['MemberList'];

        foreach ($member_list as $key => $item) {
            $contact_pool->add(ContactFactory::create($item));
        }

        $contact_list = $this->init_response['ContactList'];

        foreach($contact_list as $key => $item) {
            $contact_pool->add(ContactFactory::create($item));
        }
    }

    /**
     * 轮询登录状态
     *
     * @return int
     */
    public function pollingLogin()
    {
        $max_times = 10;

        for ($retry_times = 0; $retry_times <= $max_times; $retry_times++) {

            $code = app()->api->getLoginStatus();

            switch ($code) {
                case self::LOGIN_SUCCESS:
                    Console::log('登录成功.');
                    //获取令牌数据
                    $token = app()->api->getToken();
                    //设置令牌数据
                    app()->auth->setToken($token);
                    return $code;
                case self::LOGIN_CONFIRM:

                    if (!isset($click_btn)) {
                        $click_btn = true;
                        Console::log("请在手机上点击登录按钮.");
                    }

                    $retry_times -= 1;

                    sleep(1);

                    break;
                default:
                    return $code;
            }
        }

        return false;
    }

    /**
     * 获得二维码实际内容并打印到控制台
     */
    public function openQRcode()
    {

        $max_times = 10;

        for ($retry_times = 0; $retry_times <= $max_times; $retry_times++) {
            $uuid = app()->api->getUuid();

            if ($uuid === false) {
                continue;
            }

            app()->auth->setUuid($uuid);

            $img_url = 'https://login.weixin.qq.com/qrcode/' . $uuid;

            call_user_func(function() use($img_url){
                $content = file_get_contents($img_url);
                $path = app()->config->qrcode_img_path;
                file_put_contents($path . DIRECTORY_SEPARATOR . 'qrcode.png', $content);
            });

            $text = 'https://login.weixin.qq.com/l/' . $uuid;
            $this->generateQRcode($text);
            return;
        }

        Console::log("获取二维码失败", Console::ERROR);
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