<?php
/**
 * @desc: 基类
 * @author: wanghongfeng
 * @date: 2018/7/25
 * @time: 下午6:12
 */

namespace Core\Platform\Weixin;


use Core\Services\Curl;
use Exception;

abstract class Base
{
    /**
     * @var string
     */
    protected $apiHost = 'https://api.weixin.qq.com';

    /**
     * @var string
     */
    protected $mpHost = 'https://mp.weixin.qq.com';

    /**
     * @var string
     */
    protected $openHost = 'https://open.weixin.qq.com';

    /**
     * @param string $uri
     * @param string $query
     * @param boolean $accessToken
     * @param string $urlType
     * @return mixed
     */
    protected function post($uri, $query = null, $accessToken = true, $urlType = 'api'){
        $url = $this->getUrl($uri, [], $accessToken, $urlType);
        $result = Curl::post($url, $query);
        return $this->jsonDecode($result);
    }

    /**
     * @param string $uri
     * @param array $query
     * @param boolean $accessToken
     * @param string $urlType
     * @return mixed
     */
    protected function get($uri, $query = [], $accessToken = true,  $urlType = 'api'){
        $url = $this->getUrl($uri, $query, $accessToken, $urlType);
        $result = Curl::get($url);
        return $this->jsonDecode($result);
    }

    /**
     * @desc 解析json
     * @param $result
     * @return mixed
     * @throws Exception
     */
    protected function jsonDecode($result){
        $result = json_decode($result, true);
        if($result == null || !is_array($result)) {
            throw new Exception( 'weixin api access error', 50000);
        }
        if(isset($result['errcode']) && $result['errcode'] != 0) {
            throw new Exception($result['errmsg'], $result['errcode']);
        }
        return $result;
    }

    /**
     * @desc 获取url
     * @param string $uri
     * @param array $query
     * @param boolean $accessToken
     * @param string $urlType
     * @return string
     */
    protected function getUrl($uri, $query = [], $accessToken = true, $urlType = 'api'): string
    {
        if($accessToken){
            $query['access_token'] = $this->token();
        }
        $uri .= '?'.http_build_query($query);
        return $this->{$urlType.'Host'}.$uri;
    }

    /**
     * @desc 重定向跳转
     * @param string $url
     */
    protected function redirect(string $url)
    {
        header("Location:{$url}");
    }
}