<?php
namespace Core\Utils\Api;

use Core\Base\Log;
use Yaf\Request\Http;

/**
 * @desc  Api 输出，目前主要支持Json格式
 * Class ApiResult
 * @package Core\Utils\Api
 */
class ApiResult
{

    /**
     * 输出模板，默认成功
     * 部分接口可能会有额外参数
     *
     * @var array
     */
    public $result = array(
        'code' => 200,
        'msg' => 'ok',
        'time' => '',
        'data' => '',
    );


    /**
     * 支持js var, json, jsonp
     * @var string
     */
    private $resultFormat = 'json';

    /**
     * @var string
     */
    private $resultCallBack = '';

    /**
     * 输出变量名
     * @var string
     */
    private $reaultVarName = '';

    /**
     *
     * @var string 域名
     */
    private $domain = '';

    /**
     * @desc 初始化，根据参数返回输出类型
     * ApiResult constructor.
     */
    public function __construct()
    {
        //检查设置
        $http = new Http();
        $callback = $http->get('callback');
        $varName = $http->get('varname');

        switch (true) {
            case !is_null($callback):
                if (preg_match("/^[a-zA-Z][a-zA-Z0-9_\.]+$/", $callback)) {
                    $this->setReturnTypeJsonp($callback);
                }
                break;
            case !is_null($varName):
                if (preg_match("/^[a-zA-Z][a-zA-Z0-9_\.]+$/", $callback)) {
                    $this->setReturnTypeJsVar($varName);
                }
                break;
            default:
                $this->setReturnTypeJson();
                break;
        }
    }

    /**
     * @desc 设置返回 js var
     * @param $varName
     * @return $this
     */
    public function setReturnTypeJsVar($varName)
    {
        $this->resultFormat = 'jsvar';
        $this->reaultVarName = $varName;
        return $this;
    }

    /**
     * @desc 设置 返回json
     * @return $this
     */
    public function setReturnTypeJson()
    {
        $this->resultFormat = 'json';
        return $this;
    }

    /**
     * @desc 返回类型jsonp
     * @param $callback
     * @return $this
     */
    public function setReturnTypeJsonp($callback)
    {
        $this->resultFormat = 'jsonp';
        $this->resultCallBack = $callback;
        return $this;
    }

    /**
     * @desc 返回类型html
     * @return $this
     */
    public function setReturnTypeHtml(){
        $this->resultFormat = 'html';
        return $this;
    }

    /**
     * @desc 返回json
     */
    private function toJson()
    {
        return json_encode($this->result);
    }

    /**
     * 返回jsonp形式
     * var a = {};
     */
    private function toJsonp()
    {
        return htmlentities($this->resultCallBack) . '(' . json_encode($this->result) . ');';
    }

    /**
     * @desc 返回js变量形式 var a = {};
     * @return string
     */
    private function toJsVar()
    {
        return 'var ' . htmlentities($this->resultCallBack) . '=' . json_encode($this->result) . ';';
    }

    /**
     * @desc 返回类型html
     * @param $domain
     * @return $this
     */
    public function setDomain($domain){
        $this->domain = $domain;
        $this->setReturnTypeHtml();
        return $this;
    }

    /**
     * @desc 设置 code
     * @param int $code
     * @return $this
     */
    public function setCode($code)
    {
        $this->result['code'] = $code;
        return $this;
    }

    /**
     * @desc 获取 code
     * @return mixed
     */
    public function getCode()
    {
        return $this->result['code'];
    }

    /**
     * @desc 获取信息
     * @return mixed
     */
    public function getMsg()
    {
        return $this->result['msg'];
    }

    /**
     * @desc 设置信息
     * @param string $msg
     * @return $this
     */
    public function setMsg($msg)
    {
        $this->result['msg'] = $msg;
        return $this;
    }

    /**
     * @desc 设置数据
     * @param mixed int $data
     * @return $this
     */
    public function setData($data)
    {
        $this->result['data'] = $data;
        return $this;
    }

    /**
     * @desc 获取数据
     * @return mixed
     */
    public function getData()
    {
        return $this->result['data'];
    }

    /**
     * 设置time
     * @return $this
     */
    public function setTime() {
        $this->result['time'] = time();
        return $this;
    }

    /**
     * @desc 默认错误
     * @param \Throwable|mixed $msg
     * @param int $code
     * @param \Throwable $exception
     * @return $this
     */
    public function setError($msg = '失败', $code = -1000,  \Throwable $exception = null )
    {
        if(is_object($msg)){
            $code = $msg->getCode();
            $msg = $msg->getMessage();
        }

        $this->setTime();
        $this->setCode($code);
        $this->setMsg($msg);
        !empty($exception) and Log::write(LOG_ERR, $exception);//记录错误日志
        return $this;
    }

    /**
     * @desc 默认成功
     * @param mixed $data
     * @param int $code
     * @return $this
     */
    public function setSuccess($data = '', $code = 200)
    {
        $this->setTime();
        $this->setMsg('ok');
        $this->setCode($code);
        $this->setData($data);
        return $this;
    }

    /**
     * @desc 设置返回值
     * @param $name
     * @param $value
     */
    public function __set($name, $value)
    {
        switch ($name) {
            case 'code':
            case 'msg':
            case 'data':
                $this->result[$name] = $value;
                break;
        };
    }

    /**
     * @desc 转字符串
     * @return string
     */
    public function __toString()
    {
        $this->outputHeader();
        $result = '';
        switch ($this->resultFormat) {
            case 'json':
                $result = $this->toJson();
                break;
            case 'jsonp':
                $result = $this->toJsonp();
                break;
            case 'jsvar':
                $result = $this->toJsVar();
                break;
            case 'html':
                if (empty($this->domain)) {
                    $result = $this->toJson();
                } else {
                    $result = "<html><head><script>document.domain='{$this->domain}'</script></head><body>" . $this->toJson() . "</body></html>";
                }
                break;
            default:
                break;
        }
        $this->writelog($result);//写日志
        return $result;
    }

    /**
     * @desc 编码支持
     */
    public function outputHeader()
    {
        switch ($this->resultFormat) {
            case 'json':
                header('Content-Type: application/json; charset=utf-8');
                break;
            case 'jsonp':
                header('Content-Type: application/javascript; charset=utf-8');
                break;
            case 'jsvar':
                header('Content-Type: application/javascript; charset=utf-8');
                break;
            default:
                header('Content-Type: text/html; charset=utf-8');
                break;
        }
    }

    /**
     * @desc 写日志
     * @param $message
     */
    public function writelog($message) {
        $debugInfo = debug_backtrace();
        Log::write(LOG_INFO, $message, ['line'=>$debugInfo[1]['line'], 'file'=>$debugInfo[1]['file']]);
    }

    /**
     * @return self
     */
    public static function getInstance()
    {
        static $obj = null;
        return empty($obj) ? ($obj = new self()) : $obj;
    }
}