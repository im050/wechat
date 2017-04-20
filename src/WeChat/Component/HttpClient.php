<?php
namespace Im050\WeChat\Component;

use Exception;

/**
 * Class HttpClient
 * @author memory
 */
class HttpClient
{

    public $curl = null;

    public $status = 0;

    public $query_uri = '';

    /**
     * default config
     *
     * @var array
     */
    public $config = [
        'timeout' => 35,
        //'cookie' => '',
        //'header' => [],
        'useragent' => 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)',
        'return_transfer' => 1,
        //'referer' => '',
        //'cookiejar' => '',
        //'cookiefile' => '',
        'ssl_verify_peer' => false,
        'ssl_verify_host' => 2,
        'follow_location' => 0,
        'encoding' => 'gzip',
    ];

    public $params_map = [
        'timeout' => CURLOPT_TIMEOUT,
        'cookie' => CURLOPT_COOKIE,
        'header' => CURLOPT_HEADER,
        'user_agent' => CURLOPT_USERAGENT,
        'return_transfer' => CURLOPT_RETURNTRANSFER,
        'referer' => CURLOPT_REFERER,
        'cookiejar' => CURLOPT_COOKIEJAR,
        'cookiefile' => CURLOPT_COOKIEFILE,
        'ssl_verify_peer' => CURLOPT_SSL_VERIFYPEER,
        'ssl_verify_host' => CURLOPT_SSL_VERIFYHOST,
        'follow_location' => CURLOPT_FOLLOWLOCATION,
        'encoding' => CURLOPT_ENCODING
    ];

    public function __construct($config = array())
    {
        $this->config = array_merge($this->config, $config);
    }

    public function init()
    {
        if ($this->curl == null) {
            $this->curl = curl_init();
            curl_setopt($this->curl, CURLINFO_HEADER_OUT, true);
        }
    }

    public function updateOption($config)
    {
        if (is_null($this->curl)) {
            $this->init();
        }

        //合并自定义参数
        $config = array_merge($this->config, $config);

        foreach ($config as $key => $val) {
            if ($val == '') {
                continue;
            }
            if (!isset($this->params_map[$key])) {
                continue;
            }
            if ($key == 'cookie' && is_array($val)) {
                $val = $this->parseCookie($val);
            }
            curl_setopt($this->curl, $this->params_map[$key], $val);
        }
        return $config;
    }

    /**
     * 关闭CURL句柄
     */
    public function close()
    {
        if ($this->curl != null) {
            curl_close($this->curl);
            $this->curl = null;
        }
    }

    /**
     * 获得请求内容
     * @return mixed
     * @throws Exception
     */
    public function response()
    {
        if ($this->curl == null) {
            $this->init();
        }

        $data = curl_exec($this->curl);

        if ($data === false) {
            throw new Exception(curl_error($this->curl));
        }

        $this->status = curl_getinfo($this->curl, CURLINFO_HTTP_CODE);

        return $data;
    }

    /**
     * 发起POST请求
     *
     * @param string $uri
     * @param array $data
     * @param array $config
     * @return mixed
     */
    public function post($uri = '/', $data = array(), $config = [])
    {
        return $this->request($uri, $data, 'post', $config);
    }

    /**
     * 将数组解析为Cookie字符串
     *
     * @return boolean
     */
    public function parseCookie($cookie_string)
    {
        $data = array();
        foreach ($cookie_string as $key => $val) {
            $data[] = $key . "=" . $val;
        }
        $array_cookies_string = '';
        if (!empty($data)) {
            $array_cookies_string = implode(";", $data);
        }
        if (empty($cookie_string)) {
            $cookie_string = $array_cookies_string;
        } else {
            $cookie_string .= ";" . $array_cookies_string;
        }
        return $cookie_string;
    }


    /**
     * 发起GET请求
     *
     * @param string $uri
     * @param array $data
     * @param array $config
     * @return array|mixed
     */
    public function get($uri = '/', $data = array(), $config = [])
    {
        if (!empty($data)) {
            $query_string = http_build_query($data);
            if (stripos($uri, "?") === FALSE) {
                $link_symbol = '?';
            } else {
                $link_symbol = '&';
            }
            $uri .= $link_symbol . $query_string;
        }
        return $this->request($uri, [], 'get', $config);
    }

    /**
     * 发起请求
     *
     * @param $uri
     * @param $data
     * @param string $method
     * @param array $config
     * @return mixed
     */
    public function request($uri, $data = [], $method = 'get', $config = [])
    {
        $this->init();

        $config = $this->updateOption($config);

        if ($method == 'post') {
            curl_setopt($this->curl, CURLOPT_POST, 1);
            curl_setopt($this->curl, CURLOPT_POSTFIELDS, $data);
        }

        $this->setURI($uri);

        $data = $this->response();

        $this->close();

        return $data;
    }

    /**
     * 设置请求目标地址
     *
     * @param $uri
     */
    public function setURI($uri)
    {
        curl_setopt($this->curl, CURLOPT_URL, $uri);
        $this->query_uri = $uri;
    }

    /**
     * 获取最近请求记录
     *
     * @return string
     */
    public function getQueryURI()
    {
        return $this->query_uri;
    }

    /**
     * 设置参数
     *
     * @param $param
     * @param $value
     */
    public function setConfig($param, $value)
    {
        $this->config[$param] = $value;
    }
}