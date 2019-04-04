<?php
namespace Im050\WeChat\Component;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\FileCookieJar;

/**
 * Class HttpClient
 * @author memory
 */
class HttpClient
{

    private $client;

    /**
     * @var FileCookieJar|null
     */
    private $cookieJar;

    /**
     * default config
     *
     * @var array
     */
    private $config = [
        'timeout' => 60,
        'connect_timeout' => 10,
        'cookies' => true,
        'headers' => [
            'User-Agent' => 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)',
            'Accept'     => 'application/json',
            'Accept-Encoding' => 'gzip'
        ],
        'allow_redirects' => false,
        'verify' => true,
    ];

    /**
     * @return Client
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * @param Client $client
     * @return HttpClient
     */
    public function setClient($client)
    {
        $this->client = $client;
        return $this;
    }

    /**
     * @return FileCookieJar|null
     */
    public function getCookieJar()
    {
        return $this->cookieJar;
    }

    /**
     * @param FileCookieJar|null $cookieJar
     * @return HttpClient
     */
    public function setCookieJar($cookieJar)
    {
        $this->cookieJar = $cookieJar;
        return $this;
    }

    public function __construct($config = array())
    {
        $this->config = array_merge($this->config, $config);
    }

    public function init() {
        $this->cookieJar = new FileCookieJar($this->config['cookiefile_path'], true);
        $this->config['cookies'] = $this->cookieJar;
        $this->client = new Client($this->config);
    }

    /**
     * 发起POST请求
     *
     * @param string $url
     * @param array $query
     * @param array|bool $array
     * @return mixed
     */
    public function post($url = '/', $query = [], $array = false)
    {
        $key = is_array($query) ? 'form_params' : 'body';

        $content = $this->request($url, 'POST', [$key => $query]);

        return $array ? json_decode($content, true) : $content;
    }


    /**
     * 发起GET请求
     *
     * @param string $url
     * @param array $data
     * @param array $options
     * @return array|mixed
     */
    public function get($url = '/', $data = [], array $options = [])
    {
        $queryString = http_build_query($data);
        if (!empty($queryString)) {
            $url .= "?" . $queryString;
        }
        return $this->request($url, 'GET', $options);
    }

    public function request($url, $method = 'GET', $options = [], $retry = false)
    {
        //var_dump($this->getClient()->getConfig('timeout'));
        //var_dump($this->getClient()->getConfig('cookies'));
        try {
            $options = array_merge(['verify' => false], $options);
            $response = $this->getClient()->request($method, $url, $options);
            $this->cookieJar->save($this->config['cookiefile_path']);
            return $response->getBody()->getContents();
        } catch (\Exception $e) {
            Console::log($url.$e->getMessage(), Console::ERROR);
            if (!$retry) {
                return $this->request($url, $method, $options, true);
            }
            return false;
        }
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