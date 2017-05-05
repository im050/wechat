<?php
/*
 * 自定义快捷函数
 */

if (!function_exists('http')) {
    /**
     * 获取HTTP CLIENT
     *
     * @return \Im050\WeChat\Component\HttpClient|null
     */
    function http()
    {
        static $http_client = null;
        if ($http_client == null) {
            $http_client = new \Im050\WeChat\Component\HttpClient();
        }
        return $http_client;
    }
}

if (!function_exists('app')) {
    /**
     * 获取应用容器
     *
     * @return \Im050\WeChat\Core\Application|null
     */
    function app()
    {
        return \Im050\WeChat\Core\Application::getInstance();
    }
}

if (!function_exists('uri')) {
    /**
     * 获取请求地址
     *
     * @param $name
     * @return mixed|null
     */
    function uri($name)
    {
        static $servers = [
            'base_uri'  => 'https://wx.qq.com',
            'login_uri' => 'https://login.wx.qq.com',
            'push_uri'  => 'https://webpush.wx.qq.com',
            'file_uri'  => 'https://file.wx.qq.com'
        ];
        if (isset($servers[$name])) {
            return $servers[$name];
        } else {
            return null;
        }
    }
}

if (!function_exists('checkBaseResponse')) {
    /**
     * 校验数据是否正确返回
     *
     * @param $data
     * @return bool
     */
    function checkBaseResponse($data)
    {
        return intval($data['BaseResponse']['Ret']) == 0;
    }
}

if (!function_exists('members')) {
    /**
     * 获取联系人列表
     *
     * @return \Im050\WeChat\Collection\Members|null
     */
    function members()
    {
        return \Im050\WeChat\Collection\Members::getInstance();
    }
}

if (!function_exists('config')) {
    /**
     * 获取或设置配置
     *
     * @param $param
     * @param $value
     * @return mixed|null
     */
    function config($param, $value = '')
    {
        $config = \Im050\WeChat\Component\Config::getInstance();
        if (empty($value)) {
            return $config->get($param);
        } else {
            $config->set($param, $value);
            return true;
        }
    }
}