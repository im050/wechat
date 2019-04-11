<?php
namespace Im050\WeChat\Core;

use Endroid\QrCode\QrCode;
use Im050\WeChat\Collection\Element\Group;
use Im050\WeChat\Component\Console;
use Im050\WeChat\Component\Utils;
use Im050\WeChat\Exception\InvalidTokenException;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Output\ConsoleOutput;
use Im050\WeChat\Component\Logger;

class LoginService
{

    const LOGIN_SUCCESS = 200;

    const LOGIN_TIMEOUT = 408;

    const LOGIN_CONFIRM = 201;

    public $initResponse = [];

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
                exit(0);
            }
            if ($flag == LoginService::LOGIN_TIMEOUT) {
                Console::log("扫码二维码超时，正在为您重新生成二维码...");
                continue;
            }
            if ($flag != LoginService::LOGIN_SUCCESS) {
                Console::log("程序运行异常，请重新启动", Console::ERROR);
                exit(0);
            }
            Console::log("正在初始化账号数据...");
            $uin = app()->auth->uin;
            $sid = app()->auth->sid;
            $skey = app()->auth->skey;
            $passTicket = app()->auth->pass_ticket;
            $response = app()->api->webWxInit($uin, $sid, $skey, $passTicket);
            $this->initResponse = $response;
            if (!checkBaseResponse($response)) {
                Console::log("初始化失败，请重新运行本程序", Console::ERROR);
                exit(0);
            } else {
                app()->account->setUser($response['User']);
                app()->syncKey->setSyncKey($response['SyncKey']['List']);
                return true;
            }
        } while (true);
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
            Console::log("加载缓存通行证失败...");
            return false;
        }

        //尝试初始化
        $response = app()->api->webWxInit('xuin=' . app()->auth->uin, app()->auth->sid);
        $this->initResponse = $response;
        if (!checkBaseResponse($response)) {
            Console::log("免扫码登录失败..." . $response['BaseResponse']['Ret']);
            return false;
        } else {
            //初始化成功更新数据
            app()->account->setUser($response['User']);
            app()->syncKey->setSyncKey($response['SyncKey']['List']);
            //更新skey和登录时间
            app()->auth->skey = isset($response['SKey']) ? $response['SKey'] : app()->auth->skey;
            app()->keymap->setMultiple([
                'skey'       => app()->auth->skey,
                'login_time' => time()
            ])->save();
            //更新接口host信息
            app()->api->modifyUri(app()->keymap->get('uri_host'));
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
        $lastLoginTime = app()->keymap->get('login_time');
        $status = false;
        if (time() - $lastLoginTime <= 700) {
            $status = $this->tryLogin();
        }
        if (!$status) {
            $this->cleanCookies();
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
        $members = app()->members;

        try {
            $data = app()->api->getContact();
        } catch (\Exception $e) {
            app()->log->error("获取联系人失败", $e);
            return ;
        }

        //处理contact接口获取的用户列表
        $memberList = $data['MemberList'];
        foreach ($memberList as $key => $item) {
            $members->push($item);
        }

        //处理wxwebinit接口获取的用户列表
        $contactList = $this->initResponse['ContactList'];
        foreach ($contactList as $key => $item) {
            $members->push($item);
        }

        Console::log("初始化群成员数据信息...");
        //初始化群成员
        $groupList = $members->getGroups();
        $batchUsername = [];
        foreach($groupList as $key => $item) {
            array_push($batchUsername, $item['UserName']);
        }

        $batchInfo = app()->api->getBatchContact($batchUsername);
        $batchContactList = $batchInfo['ContactList'];
        foreach ($batchContactList as $key => $item) {
            $memberList = $item['MemberList'];
            /** @var Group $group */
            $group = $groupList->getContactByUserName($item['UserName']);
            if ($group) {
                $group->setMemberList($memberList);
            } else {
                Console::log("未找到群 " . $item['UserName'] . "...");
            }
        }

        Console::log(
            "共初始化" .
            "联系人：" . members()->getContacts()->count() . "个," .
            "群组：" . members()->getGroups()->count() . "个," .
            "公众号：" . members()->getOfficials()->count() . "个, " .
            "特殊号：" . members()->getSpecials()->count() . "个。"
        );

    }

    /**
     * 轮询登录状态
     *
     * @return int
     */
    public function pollingLogin()
    {
        $maxTimes = 10;
        for ($retryTimes = 0; $retryTimes <= $maxTimes; $retryTimes++) {
            try {
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
                        if (!isset($clickBtn)) {
                            $clickBtn = true;
                            Console::log("请在手机上点击登录按钮.");
                        }
                        $retryTimes -= 1;
                        sleep(1);
                        break;
                    default:
                        return $code;
                }
            } catch (InvalidTokenException $e) {
                app()->log->error($e->getMessage());
                $this->cleanCookies();
                $retryTimes -=1;
            }
        }

        return false;
    }

    /**
     * 获得二维码实际内容并打印到控制台
     */
    public function openQRcode()
    {
        $maxTimes = 10;
        for ($retryTimes = 0; $retryTimes <= $maxTimes; $retryTimes++) {
            $uuid = app()->api->getUuid();
            if ($uuid === false) {
                continue;
            }
            app()->auth->setUuid($uuid);
            if (config('save_qrcode')) {
                //下载二维码图片
                $imgUrl = 'https://login.weixin.qq.com/qrcode/' . $uuid;
                FileSystem::download($imgUrl, config('tmp_path') . '/qrcode.png');
            }
            //生成二维码在控制台
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
        $qrCode = new QrCode($text);
        $matrix = $qrCode->getData()['matrix'];
        foreach ($matrix as $line) {
            $output->write($pxMap[0]);
            foreach ($line as $item) {
                $output->write($pxMap[$item]);
            }
            $output->writeln($pxMap[0]);
        }
    }

    public function cleanCookies() {
        is_file(config('cookiefile_path')) && unlink(config('cookiefile_path'));
    }

}