<?php
/**
 * @desc:
 * @author: wanghongfeng
 * @date: 2017/7/13
 * @time: 下午4:00
 */

namespace Core\Utils\Tools\Weixin;

use Core\Cache\redis;
use Core\Services\Curl;
use Exception;

trait Core
{

    /**
     * @desc 第三方用户唯一凭证
     * @var string
     */
    private $appid = '';

    /**
     * @desc 第三方用户唯一凭证密钥，即appsecret
     * @var string
     */
    private $secret = '';

    /**
     * @desc redis
     * @var redis
     */
    private $redis = null;

    /**
     * @des 微信 redis key
     * @var string
     */
    private $wxNameSpace = 'tencent:wx:';

    /**
     * @desc api URl
     * @var string
     */
    private $apiUrl = 'https://api.weixin.qq.com';

    /**
     * @desc mp URl
     * @var string
     */
    private $mpUrl = 'https://mp.weixin.qq.com';

    /**
     * @var string
     */
    private $openUrl = 'https://open.weixin.qq.com';


    /**
     * @desc token URL
     * @var string
     */
    private $tokenUri = '/cgi-bin/token';

    /**
     * @desc 获取微信token
     * @return mixed
     */
    protected function token() {
        $query = [];
        $query['grant_type'] = 'client_credential';
        $query['appid'] = $this->getAppid();
        $query['secret'] = $this->getSecret();
        $result = $this->get($this->tokenUri, $query);
        $result = $this->jsonDecode($result,'weixin:ticket:fail-');
        return $result;
    }

    /**
     * @param $uri
     * @param string $query
     * @param string $urlType
     * @return mixed
     */
    protected function post($uri, $query = null, $urlType = 'api'){
        $url = $this->getUrl($uri, [], $urlType);
        return Curl::post($url, $query);
    }

    /**
     * @param $uri
     * @param array $query
     * @param string $urlType
     * @return mixed
     */
    protected function get($uri, $query = [], $urlType = 'api'){
        $url = $this->getUrl($uri, $query, $urlType);
        return Curl::get($url);
    }

    /**
     * @desc 获取
     * @return string
     */
    protected function getAccessToken() {
        $timeOut = 7100;//超时时间
        $key = $this->wxNameSpace.'access_token';
        $access_token = $this->getRedis()->slave()->get($key);
        if(empty($access_token)) {
            $result = $this->token();//服务端获token
            $access_token = $result['access_token'];
            $this->getRedis()->master()->set($key, $access_token, $timeOut);
        }
        return $access_token;
    }


    /**
     * @desc 解析json
     * @param $result
     * @param $error
     * @return mixed
     * @throws Exception
     */
    protected function jsonDecode($result, $error){
        $result = json_decode($result, true);
        if(!is_array($result)) {
            throw new Exception("{$error}{$result}");
        }
        if(isset($result['errcode']) && $result['errcode'] != 0) {
            throw new Exception($error.json_encode($result));
        }
        return $result;
    }

    /**
     * @desc 获取url
     * @param string $uri
     * @param array $query
     * @param string $urlType
     * @return string
     */
    protected function getUrl($uri, $query = [], $urlType = 'api'): string
    {
        $url = '';
        if(empty($query)){
            $query['access_token'] = $this->getAccessToken();
        }
        $uri .= '?'.http_build_query($query);
        if($urlType == 'api') {
            $url = $this->apiUrl.$uri;
        } else if($urlType == 'mp'){
            $url = $this->mpUrl.$uri;
        } else if($urlType == 'open'){
            $url = $this->openUrl.$uri;
        }
        return $url;
    }

    /**
     * @desc 重定向跳转
     * @param string $url
     */
    protected function redirect(string $url)
    {
        header("Location:{$url}");
    }


    /**
     * @return string
     */
    protected function getAppid(): string
    {
        return $this->appid;
    }

    /**
     * @param string $appid
     */
    public function setAppid(string $appid)
    {
        $this->appid = $appid;
    }

    /**
     * @return string
     */
    protected function getSecret(): string
    {
        return $this->secret;
    }

    /**
     * @param string $secret
     */
    public function setSecret(string $secret)
    {
        $this->secret = $secret;
    }

    /**
     * @return redis
     */
    protected function getRedis(): redis
    {
        return $this->redis;
    }

    /**
     * @param redis $redis
     */
    public function setRedis(redis $redis)
    {
        $this->redis = $redis;
    }

}