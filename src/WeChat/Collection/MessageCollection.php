<?php
/**
 * Created by PhpStorm.
 * User: linyulin
 * Date: 17/4/24
 * Time: 上午11:08
 */

namespace Im050\WeChat\Collection;


use Illuminate\Support\Collection;
use Im050\WeChat\Component\Logger;
use Im050\WeChat\Message\Formatter\Message;

class MessageCollection extends Collection
{

    /**
     * 最多存储多少条临时消息记录
     *
     * @var int
     */
    public $maxItems = 2048;

    protected static $_instance = null;

    public static function getInstance()
    {
        if (self::$_instance === null) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * 增加一条消息记录
     *
     * @param Message|array $message
     * @return $this
     */
    public function add($message) {
        if ($message instanceof Message) {
            $msgId = $message->getMessageID();
            $raw = $message->raw();
        } else {
            $msgId = $message['MsgId'];
            $raw = $message;
        }

        if ($this->count() >= $this->maxItems) {
            $this->pop();
        }

        return $this->prepend($raw, $msgId);
    }

    /**
     * 根据username获取他的消息记录
     *
     * @param $username
     * @return mixed|static
     */
    public function getMessageByUserName($username) {
        return $this->find($username, 'FromUserName', false);
    }

    /**
     * 根据键值对应查找
     *
     * @param $search
     * @param $key
     * @param bool $first
     * @param bool $blur
     * @return mixed|static
     */
    public function find($search, $key, $first = false, $blur = false)
    {
        $objects = $this->filter(function ($item) use ($search, $key, $blur) {

            if (!isset($item[$key])) return false;

            if ($blur && str_contains($item[$key], $search)) {
                return true;
            } elseif (!$blur && $item[$key] === $search) {
                return true;
            }

            return false;
        });

        return $first ? $objects->first() : $objects;
    }
}