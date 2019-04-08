<?php
/**
 * Created by PhpStorm.
 * User: linyulin
 * Date: 2019/4/8
 * Time: 6:17 PM
 */

namespace Im050\WeChat\Message\Formatter;


class Transfer extends Message
{
    private $fee;

    private $transactionId;

    private $memo;

    /**
     * via Vbot
     */
    public function handleMessage()
    {
        $array = (array) simplexml_load_string($this->content, 'SimpleXMLElement', LIBXML_NOCDATA);
        $des = (array) $array['appmsg']->des;
        $fee = (array) $array['appmsg']->wcpayinfo;

        $this->memo = is_string($fee['pay_memo']) ? $fee['pay_memo'] : null;
        $this->fee = substr($fee['feedesc'], 3);
        $this->transactionId = $fee['transcationid'];

        $this->string = current($des);
    }


}