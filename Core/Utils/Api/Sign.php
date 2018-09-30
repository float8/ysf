<?php
namespace Core\Utils\Api;

use Core\Base\Exception;
use Core\Utils\Tools\ClientServer;
use Core\Utils\Tools\Fun;

/**
 * @desc 签名类
 * @header 信息
 * Api-Key //api key
 * Api-Nonce //随机串
 * Api-Timestamp //时间戳
 * Api-Signature //签名
 */
class Sign
{

    /**
     * @desc 请求数据类型
     * @var string
     */
    private $requestType = 'body';

    /**
     * @desc ip白名单
     * @var array
     */
    private $ipWhiteList = null;

    /**
     * @desc 过期限制时间(秒)
     * @var int
     */
    private $timeLimit = 60;

    /**
     * @desc 打开时间限制
     * @var boolean
     */
    private $openTimeLimit = false;

    /**
     * @desc api秘钥
     * @var boolean
     */
    private $apiSecret = null;

    /**
     * Sign constructor.
     * @param bool $openTimeLimit 打开事件限制
     * @param int $timeLimit 限制时间 秒
     */
    public function __construct($openTimeLimit = false, $timeLimit = 60)
    {
        $this->timeLimit = $timeLimit;
        $this->openTimeLimit = $openTimeLimit;
    }

    /**
     * @desc 设置签名头信息
     * @throws Exception
     */
    private function setSignHeader()
    {
        $keys = ['HTTP_API_KEY', 'HTTP_API_NONCE', 'HTTP_API_TIMESTAMP', 'HTTP_API_SIGNATURE'];
        foreach ($keys as $key) {
            $val = Fun::get($_SERVER, $key);
            if (empty($val)) {
                throw new Exception('Invalid data', -30000);
            }
        }
    }

    /**
     * @desc 设置请求类型
     * @param string $requestType
     * @return $this
     */
    public function setRequestType($requestType)
    {
        $this->requestType = $requestType;
        return $this;
    }

    /**
     * @desc 创建api秘钥
     * @param callable $apiSecret
     * @return $this
     * @throws Exception
     */
    public function setApiSecret(callable $apiSecret)
    {
        $this->apiSecret = call_user_func($apiSecret, $_SERVER['HTTP_API_KEY']);
        if (empty($this->apiSecret)) {
            throw new Exception('Illegal api key', -30001);
        }
        return $this;
    }


    /**
     * @desc 设置ip白名单
     * @param callable $ipWhiteList
     * @return $this
     */
    public function setIpWhiteList(callable $ipWhiteList)
    {
        $this->ipWhiteList = call_user_func($ipWhiteList);
        return $this;
    }

    /**
     * @desc 获取ip白名单
     * @return array
     */
    public function getClientIpWhiteList()
    {
        return $this->ipWhiteList;
    }

    /**
     * @desc 验证白名单
     * @return boolean
     */
    private function verifyIpWhiteList()
    {
        if (empty($this->ipWhiteList)) {
            return false;
        }
        $ipWhiteList = str_replace(['*', '.'], ['\d+', '\.'], $this->ipWhiteList);
        $ipWhiteList = implode('|', $ipWhiteList);
        return (bool)preg_match("/^(" . $ipWhiteList . ")$/", ClientServer::getIp());
    }

    /**
     * @desc 验证时间
     * @throws Exception
     */
    private function verifyTime()
    {
        if ($this->openTimeLimit && abs(time() - $_SERVER['HTTP_API_TIMESTAMP']) > $this->timeLimit) {
            throw new Exception('Data resubmit', -30002);
        }
    }

    /**
     * @desc 获取请求实体数据
     * @return array
     */
    private function getRequestBody()
    {
        $body = file_get_contents("php://input");//所有参数json格式
        $result = $this->toJsonDecode($body);//解析json为数组
        return $result;
    }

    /**
     * @desc 解析json为数组
     * @param $data
     * @return array|mixed
     * @throws Exception
     */
    private function toJsonDecode($data)
    {
        $result = !empty($data) ? json_decode($data, true) : [];
        //数据格式错误
        if (!empty($result) && !is_array($result)) {
            throw new Exception('Invalid data', -30003);
        }
        return $result;
    }

    /**
     * @desc 生成签名
     * @param $data
     * @return string
     */
    private function buildSign($data)
    {
        $this->verifyTime();//检测时间
        ksort($data);
        return sha1($this->apiSecret . $_SERVER['HTTP_API_NONCE'] . $_SERVER['HTTP_API_TIMESTAMP'] . json_encode($data, JSON_UNESCAPED_UNICODE));
    }

    /**
     * @uses getRequestBody
     * @desc 根据请求类型获取数据
     * @return array
     */
    private function getRequestData()
    {
        $requestType = ucfirst($this->requestType);
        $method = "getRequest{$requestType}";
        $result = [];
        if (method_exists($this, $method)) {
            $result = $this->$method();
            $result = is_array($result) ? $result : [];
        }
        return $result;
    }

    /**
     * @desc 验证签名
     * @param boolean $verify
     * @throws Exception
     * @return mixed
     */
    public function verify($verify = true)
    {
        $result = $this->getRequestData();//获取请求数据
        //不检查签名
        if (!$verify) {
            return $result;
        }
        //设置签名头信息
        $this->setSignHeader();
        //验证白名单
        if ($this->verifyIpWhiteList()) {
            return $result;
        }
        //签名检测
        if (strcmp($this->buildSign($result), $_SERVER['HTTP_API_SIGNATURE'])) {
            throw new Exception('Illegal signature', -30004);
        }
        return $result;
    }
}