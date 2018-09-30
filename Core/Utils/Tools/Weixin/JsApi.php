<?php
/**
 * @desc:
 * @author: wanghongfeng
 * @date: 2017/7/13
 * @time: 下午8:04
 */

namespace Core\Utils\Tools\Weixin;


trait JsApi
{
    /**
     * @var string
     */
    private $jsAPiTicketUri = '/cgi-bin/ticket/getticket';
    /**
     * @desc 获取js ticket
     * @return array
     */
    private function getticket() :array
    {
        $query = [];
        $query['access_token'] = $this->getAccessToken();
        $query['type'] = 'jsapi';
        $result = $this->get($this->jsAPiTicketUri, $query);
        $result = $this->jsonDecode($result, 'weixin:ticket:getticket:fail-');
        return $result;
    }

    /**
     * @desc 获取js ticket字符串
     * @return string
     */
    private function getTicketStr() : string
    {
        $timeOut = 7100;//超时时间
        $key = $this->wxNameSpace.'jsticket';
        $ticket = $this->getRedis()->slave()->get($key);
        if(empty($ticket)) {
            $result = $this->getticket();
            $ticket = $result['ticket'];
            $this->getRedis()->master()->set($key, $ticket, $timeOut);
        }
        return $ticket;
    }

    /**
     * @desc 获取签名数据包
     * @param $url
     * @param array $jsApiList
     * @param bool $debug
     * @return array
     */
    public function getSignPackage($url, $jsApiList = [], $debug = true) :array
    {
        $signature = [];
        $signature['jsapi_ticket'] = $this->getTicketStr();
        $signature['noncestr'] = uniqid();
        $signature['timestamp'] = time();
        $signature['url'] = $url;
        ksort($signature);

        $signPackage = array(
            'debug'     => $debug,
            "appId"     => $this->getAppid(),
            "nonceStr"  => $signature['noncestr'],
            "timestamp" => $signature['timestamp'],
            "signature" => sha1(urldecode(http_build_query($signature))),
            'jsApiList' => $jsApiList,
        );
        return $signPackage;
    }
}