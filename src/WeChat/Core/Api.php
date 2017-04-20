<?php
namespace Im050\WeChat\Core;

use Im050\WeChat\Component\Utils;

class Api
{

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
            'r' => Utils::timeStamp(),
            '_' => Utils::timeStamp(),
            'skey' => $skey,
            'sid' => $sid,
            'uin' => $uin,
            'deviceid' => $device_id,
            'synckey' => SyncKey::getInstance()->string(),
        ];

        $url = uri('push_uri') . '/cgi-bin/mmwebwx-bin/synccheck';
        $content = http()->get($url, $payload);
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
            'BaseRequest' => app()->auth->base_request,
            'SyncKey' => [
                'Count' => SyncKey::getInstance()->count(),
                'List' => SyncKey::getInstance()->get()
            ],
            'rr' => Utils::timeStamp()
        ];

        $query_string = [
            'sid' => app()->auth->sid,
            'skey' => app()->auth->skey,
            'pass_ticket' => app()->auth->pass_ticket
        ];

        $url = uri('base_uri') . '/cgi-bin/mmwebwx-bin/webwxsync?' . http_build_query($query_string);
        $content = http()->post($url, json_encode($payload));
        $data = json_decode($content, JSON_OBJECT_AS_ARRAY);

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
            'skey' => $auth->skey,
            'r' => Utils::timeStamp()
        ]);
        $url = uri('base_uri') . '/cgi-bin/mmwebwx-bin/webwxgetcontact?' . $query_string;
        $content = http()->post($url);
        $data = json_decode($content, JSON_OBJECT_AS_ARRAY);

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
            'BaseRequest' => app()->auth->base_request,
            'Msg' => [
                "Type" => 1,
                "Content" => $text,
                "FromUserName" => Account::username(),
                "ToUserName" => $username,
                "LocalID" => $msg_id,
                "ClientMsgId" => $msg_id
            ]
        ];
        $data = http()->post($url, json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        $data = json_decode($data, JSON_OBJECT_AS_ARRAY);
        $flag = checkBaseResponse($data);
        return $flag;
    }

    public function getMessageImage($msg_id)
    {
        $url = uri('base_uri') . '/cgi-bin/mmwebwx-bin/webwxgetmsgimg?' . http_build_query([
                'MsgID' => $msg_id,
                'skey' => app()->auth->skey
            ]);
        $data = http()->get($url);
        return $data;
    }

}