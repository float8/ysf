<?php
namespace Core\Services;

use Core\Utils\Tools\Fun;
use Exception;
use Core\Base\Log;
use Core\Utils\Tools\ClientServer;

class Curl {

    /**
     * @param string $url
     * @param mixed $data
     * @param array $headers
     * @param array $setopt
     * @return mixed
     * @throws Exception
     */
    public static function jPost(string $url, $data = null, $headers = [], $setopt = null) {
        //获取客户端IP
        $client_ip = ClientServer::getIp();
        //默认头信息
        $defaultHeaders = [
            'content-type'=>'application/json;charset=UTF-8',
            'CLIENT-IP'=>$client_ip,
            'X-FORWARDED-FOR'=>$client_ip
        ];
        //头信息
        $headers = array_merge($defaultHeaders, $headers);
        //数据处理
        $data = is_array($data) ? json_encode($data) : $data;
        //发送post请求
        $body = self::post( $url, $data, $headers, $setopt);
        //解码json
        $result	= json_decode($body, true);
        //获取json错误
        $error = json_last_error();
        //记录日志
        Log::write($error ? LOG_ERR : LOG_INFO, "request:{$data};respons:{$body}", ['line'=>'curl', 'file'=>$url]);
        //错误抛出异常
        if ($error) {
            throw new Exception('返回数据格式错误');
        }
        return $result;
    }

    /**
     * @desc post
     * @param string $url
     * @param mixed $data
     * @param mixed $headers
     * @param mixed $setopt
     * @return mixed
     */
    public static function get($url, $data = null, $headers = null, $setopt = null) {
        $defaultSetopt = [
            CURLOPT_URL=>self::buildUrl($url, $data),//设置url
            CURLOPT_HTTPHEADER=>self::buildHeader($headers),//设置头信息
            CURLOPT_USERAGENT=>Fun::get($_SERVER, 'HTTP_USER_AGENT'),//添加浏览器信息
            CURLOPT_HEADER=>false,//启用时会将头文件的信息作为数据流输出。
            CURLOPT_NOSIGNAL=>true,//TRUE 时忽略所有的 cURL 传递给 PHP 进行的信号。在 SAPI 多线程传输时此项被默认启用，所以超时选项仍能使用。
            CURLOPT_SSL_VERIFYPEER=>false,//FALSE 禁止 cURL 验证对等证书
            CURLOPT_CONNECTTIMEOUT=>30,//连接超时
            CURLOPT_TIMEOUT=>60,//响应超时
            CURLOPT_RETURNTRANSFER=>true,//设定返回的数据是否自动显示
        ];
        //cURL 传输选项
        $setopt = empty($setopt) ? $defaultSetopt : array_merge($defaultSetopt, $setopt);
        //执行curl
        return self::exec($setopt);
    }

    /**
     * @desc post
     * @param string $url
     * @param mixed $data
     * @param mixed $headers
     * @param mixed $setopt
     * @return mixed
     */
    public static function post($url, $data = null, $headers = null, $setopt = null) {
        $defaultSetopt = [
            CURLOPT_HTTPHEADER=>self::buildHeader($headers),//设置头信息
            CURLOPT_POSTFIELDS=>self::buildQuery($data),//发送post字符串
            CURLOPT_USERAGENT=>Fun::get($_SERVER, 'HTTP_USER_AGENT'),//添加浏览器信息
            CURLOPT_URL=>$url,//设置url
            CURLOPT_HEADER=>false,//启用时会将头文件的信息作为数据流输出。
            CURLOPT_RETURNTRANSFER=>true,//设定返回的数据是否自动显示
            CURLOPT_CONNECTTIMEOUT=>30,//连接超时
            CURLOPT_TIMEOUT=>60,//响应超时
            CURLOPT_NOSIGNAL=>true,//TRUE 时忽略所有的 cURL 传递给 PHP 进行的信号。在 SAPI 多线程传输时此项被默认启用，所以超时选项仍能使用。
            CURLOPT_POST=>true,//post 请求
            CURLOPT_SSL_VERIFYPEER=>false,//FALSE 禁止 cURL 验证对等证书
        ];
        //cURL 传输选项
        $setopt = empty($setopt) ? $defaultSetopt : array_merge($defaultSetopt, $setopt);
        //执行curl
        return self::exec($setopt);
    }

    /**
     * @desc 执行curl
     * @param $setopt
     * @return mixed
     */
    public static function exec($setopt){
        $ch = curl_init();
        self::buildSetopt($ch, $setopt);
        $result = curl_exec($ch);
        if( $result === false) {
            trigger_error(curl_error($ch));
        }
        curl_close($ch);
        return $result;
    }

    /**
     * @desc 构建header
     * @param $headers
     * @return array
     */
    public static function buildHeader($headers) {
        $headerArr = [];
        if(empty($headers)) {
            return $headerArr;
        }
        if(is_string($headers)) {
            $headerArr[] = $headers;
            return $headerArr;
        }
        foreach( $headers as $k => $v ) {
            $headerArr[] = is_numeric($k) ? $v : "{$k}:{$v}";
        }
        return $headerArr;
    }

    /**
     * @desc 设置 cURL 传输选项
     * @param $ch
     * @param mixed $option
     * @param mixed $value
     */
    private static function buildSetopt($ch, $option, $value = null) {
        if(empty($ch) || empty($option)) {
            return ;
        }
        if(!is_array($option) && !is_null($value) ) {
            curl_setopt($ch, $option, $value);
            return ;
        }
        foreach ($option as $k=>$v) {
            curl_setopt($ch, $k, $v);
        }
    }

    /**
     * @desc 创建url
     * @param string $url
     * @param mixed $data
     * @return string
     */
    private static function buildUrl($url, $data) {
        if(empty($data)) {
            return $url;
        }
        $query = http_build_query($data);
        $lastChar = substr($url,-1);
        if($lastChar == '&' || $lastChar == '?'){
            return $url.$query;
        }
        if(strpos($url, '?')){
            return $url.'&'.$query;
        }
        return $url.'?'.$query;
    }

    /**
     * 创建查询字符串
     * @param mixed $data
     * @return string
     */
    private static function buildQuery($data) {
        return is_array($data)?http_build_query($data):$data;
    }
}
