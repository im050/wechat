<?php

function http() {
    static $http_client = null;
    if ($http_client == null) {
        $http_client = new \Im050\WeChat\Component\HttpClient();
    }
    return $http_client;
}

function app() {
    return \Im050\WeChat\Core\Application::getInstance();
}

function uri($name) {
    static $servers = [
        'base_uri' => 'https://wx.qq.com',
        'login_uri' => 'https://login.wx.qq.com',
        'push_uri' => 'https://webpush.wx.qq.com'
    ];
    if (isset($servers[$name])) {
        return $servers[$name];
    } else {
        return null;
    }
}

function checkBaseResponse($data) {
    return intval($data['BaseResponse']['Ret']) == 0;
}