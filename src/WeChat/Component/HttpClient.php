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


    public function init() {
        $this->cookieJar = new FileCookieJar(config('cookies.file'), true);
        config('http.cookies', $this->cookieJar);
        $this->client = new Client(config('http'));
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
            $this->cookieJar->save(config('cookies.file'));
            return $response->getBody()->getContents();
        } catch (\Exception $e) {
            Console::log($url.$e->getMessage(), Console::ERROR);
            if (!$retry) {
                return $this->request($url, $method, $options, true);
            }
            return false;
        }
    }
}