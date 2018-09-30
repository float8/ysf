<?php
/**
 * @desc: 微信核心类
 * @author: wanghongfeng
 * @date: 2018/7/25
 * @time: 下午5:58
 */

namespace Core\Platform\Weixin\MiniProgram;

use Core\Utils\Tools\Fun;
use Exception;

trait Core
{
    /**
     * @desc 小程序唯一标识
     * @var string
     */
    private $appid = null;

    /**
     * @desc 小程序的 app secret
     * @var string
     */
    private $secret = null;

    /**
     * @desc access token
     * @var string
     */
    private $accessToken = null;

    /**
     * @desc 获取token信息
     * @return string
     */
    protected function token(){
        return $this->accessToken;
    }

    /**
     * @desc 获取access token
     * @param string $accessToken  access token
     */
    public function setAccessToken($accessToken)
    {
        $this->accessToken = $accessToken;
    }

    /**
     * @desc token uri
     * @var string
     */
    private $tokenUri = '/cgi-bin/token';

    /**
     * @desc 设置 Access Token
     * @param callable $fun 函数作用：存储token
     * @return mixed
     */
    public function getAccessToken(callable $fun)
    {
        $query = [
            'grant_type'=>'client_credential',
            'appid'=>$this->getAppid(),
            'secret'=>$this->getSecret(),
        ];
        $result = $this->get($this->tokenUri, $query, false);
        return$result;
    }

    /**
     * @return string
     */
    public function getAppid(): string
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
    public function getSecret(): string
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
     * @desc 签名验证
     * @param string $signature
     * @param string $rawData
     * @param string $session_key
     * @throws Exception
     */
    private function signature($signature, $rawData, $session_key)
    {
        $_signature = sha1($rawData.$session_key);
        if(strcmp($signature, $_signature)){
            throw new Exception('Illegal signature', -50001);
        }
    }

    /**
     * 检验数据的真实性，并且获取解密后的明文
     * @param string $data 数据
     * @param string $sessionKey 会话密钥
     * @return string
     * @throws Exception
     */
    public function decryptData($data, $sessionKey)
    {
        $signature = Fun::get($data, 'signature');//签名
        $rawData = Fun::get($data, 'rawData');//签名数据
        $encryptedData = Fun::get($data, 'encryptedData');//加密的用户数据
        $iv = Fun::get($data, 'iv');//与用户数据一同返回的初始向量
        $this->signature($signature, $rawData, $sessionKey);//签名验证
        if (strlen($sessionKey) != 24) {
            throw new Exception('Illegal aes key', -41001);
        }
        $aesKey = base64_decode($sessionKey);
        if (strlen($iv) != 24) {
            throw new Exception('Illegal iv', -41002);
        }
        $aesIV = base64_decode($iv);
        $aesCipher = base64_decode($encryptedData);
        if(empty($aesCipher) || empty($aesIV)){
            throw new Exception('Decode base64 error', -41004);
        }
        $result = openssl_decrypt($aesCipher, "AES-128-CBC", $aesKey, 1, $aesIV);
        $dataObj = json_decode($result);
        if ($dataObj == NULL || $dataObj->watermark->appid != $this->getAppid()) {
            throw new Exception('Illegal Buffer', -41003);
        }
        return $result;
    }
}