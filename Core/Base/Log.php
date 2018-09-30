<?php
/**
 * @desc:记录日志
 * @author: wanghongfeng
 * @date: 2017/10/19
 * @time: 下午1:48
 */

namespace Core\Base;

use Core\Utils\Tools\ClientServer;
use Core\Utils\Tools\Fun;
use Throwable;


class Log
{
    /**
     * @desc 请求日志
     */
    public static function request()
    {
        $log = Config::app('yaf.app.log', true);//是否开启日志，默认开启
        if(!$log) {
            return ;
        }
        self::uri();//记录uri
        self::clientIp();//记录客户端ip
        self::agent();//记录浏览器信息
        self::cookie();//记录cookie日志
        self::session();//记录session日志
        self::server();//记录server日志
        self::get();//记录get日志
        self::post();//记录post日志
        self::input();//记录php输入流数据
    }

    /**
     * @desc 过滤数据
     * @param $data
     * @return mixed
     */
    public static function filter($data)
    {
        if (!is_array($data)) {
            return $data;
        }
        static $filter = null;
        //$filter = null 获取过滤字段
        if (is_null($filter)) {
            $filter = Config::app('yaf.app.filter');
        }
        //$filter数据为空直接返回
        if (empty($filter)) {
            $filter = [];
            return $data;
        }
        //把过滤字段拆分为数组
        if (!is_array($filter)) {
            $filter = explode('|', $filter);
        }
        //删除数组中的字段
        foreach ($filter as $v) {
            if (isset($data[$v])) {
                unset($data[$v]);
            }
        }
        return $data;
    }

    /**
     * @desc 记录日志
     * @param int $priority 请见syslog
     * @param \Throwable|string $message
     * @param array $trace
     * @return bool
     */
    public static function write(int $priority, $message, $trace = null)
    {
        if (empty($message)) {
            return false;
        }
        $logid = self::logid();//日志编号
        $trace = self::getTrace($message, $trace);//$trace 信息
        $traceString = is_object($message) ? $message->getTraceAsString() : ''; //获取trace字符串
        $message = is_object($message) ? $message->getMessage() : $message;//消息内容
        $log_local = Config::app('yaf.app.log_local');

        $line = Fun::get($trace, 'line');//跟踪的行号
        $file = Fun::get($trace, 'file');//跟踪的文件
        $location = empty($file) ? "[{$line}]" : "[{$line}:{$file}]";//位置
        $message = empty($traceString) ? $message : $message."\n".$traceString;
        $message = date("Y-m-d H:i:s") . " {$logid} " . PROJECT_NAME . " {$location} {$message}";//消息
        //当应用存在钩子时触发
        if(Hook::existAppHook('Log')) {
            return Hook::triggerApp('Log', 'write',[
                'logid'=>$logid,
                'trace'=>$trace,
                'traceString'=>$traceString,
                'message'=>$message,
            ]);
        }
        //写入系统日志
        return syslog($priority | $log_local, $message);
    }

    /**
     * @desc 获取跟踪信息
     * @param \Throwable|string $message
     * @param $trace
     * @return array
     */
    private static function getTrace($message, $trace)
    {
        /**
         * @desc 处理文件地址
         * @param $file
         * @return string
         */
        $_logFile = function ($file) {
            if(empty($file)){
                return '';
            }
            $file = explode(PROJECT_NAME, $file);
            return isset($file[1]) ? $file[1] : $file[0];
        };
        //$message 为异常类
        if(is_object($message)) {
            return ['file'=>$_logFile($message->getFile()),'line'=>$message->getLine()];
        }
        //根据$trace获取文件及行号
        if(is_string($message) && !empty($trace) && is_array($trace)){
            $file = Fun::get($trace, 'file');
            $line = Fun::get($trace, 'line', 0);
            return ['file'=>$_logFile($file),'line'=>$line];
        }
        //根据 debug_backtrace 获取
        if(is_string($message) && empty($trace)){
            $trace = debug_backtrace();
            return ['file'=>$_logFile($trace[0]['file']),'line'=>$trace[0]['line']];
        }
        return [];
    }

    /**
     * @desc 日志编号
     * @return int|string
     */
    public static function logid()
    {
        static $logid = null;//日志编号
        //获取logid
        if ($logid === null) {
            $logid = uniqid(random_int(1, 999999999), true);
            $logid = crc32($logid);
            $logid = sprintf("%u", $logid);
            $logid = str_pad($logid, 10, '0', STR_PAD_LEFT);
        }
        return $logid;
    }

    /**
     * @desc 记录用户浏览器信息
     */
    public static function agent()
    {
        $agent = Fun::get($_SERVER, 'HTTP_USER_AGENT');
        if (!empty($agent)) {
            self::write(LOG_INFO, $agent, ['line'=>'user_agent']);
        }
    }

    /**
     * @desc 记录uri
     */
    public static function uri()
    {
        $uri = Fun::get($_SERVER, 'REQUEST_URI');
        if (!empty($uri)) {
            self::write(LOG_INFO, $uri, ['line'=>'uri']);
        }
    }

    /**
     * @desc 记录get日志
     */
    public static function get()
    {
        if (empty($_GET)) {
            return;
        }
        $data = self::filter($_GET);
        $data = json_encode($data, JSON_UNESCAPED_UNICODE);
        self::write(LOG_INFO, $data, ['line'=>'get']);
    }

    /**
     * @desc 记录post日志
     */
    public static function post()
    {
        if (empty($_POST)) {
            return;
        }
        $data = self::filter($_POST);
        $data = json_encode($data, JSON_UNESCAPED_UNICODE);
        self::write(LOG_INFO, $data, ['line'=>'post']);
    }

    /**
     * @desc 记录输入流志
     */
    public static function input()
    {
        $data = file_get_contents('php://input');
        if (empty($data)) {
            return;
        }
        //如果是输入流并且是json,是否过滤
        $inputJsonfilter = Config::app('yaf.app.inputJsonFilter', false);
        if ($inputJsonfilter) {
            $data = json_decode($data, true);
            $data = is_array($data) ? self::filter($data) : null;
            $data = empty($data) ? null : json_encode($data, JSON_UNESCAPED_UNICODE);
        }
        self::write(LOG_INFO, $data, ['line'=>'input']);
    }

    /**
     * @desc 记录cookie日志
     */
    public static function cookie()
    {
        if (empty($_COOKIE)) {
            return;
        }
        $data = self::filter($_COOKIE);
        $data = json_encode($data, JSON_UNESCAPED_UNICODE);
        self::write(LOG_INFO, $data, ['line'=>'cookie']);
    }

    /**
     * @desc 记录session日志
     */
    public static function session()
    {
        if (empty($_SESSION)) {
            return;
        }
        $data = self::filter($_SESSION);
        $data = json_encode($data, JSON_UNESCAPED_UNICODE);
        self::write(LOG_INFO, $data, ['line'=>'session']);
    }

    /**
     * @desc 记录server日志
     */
    public static function server()
    {
        $data = self::filter($_SERVER);
        $data = json_encode($data, JSON_UNESCAPED_UNICODE);
        self::write(LOG_INFO, $data, ['line'=>'server']);
    }

    /**
     * @desc 记录访问ip
     */
    public static function clientIp()
    {
        self::write(LOG_INFO, ClientServer::getIp(), ['line'=>'client_ip']);
    }

    /**
     * @desc 记录运行时间
     */
    public static function runTime()
    {
        $runtime = microtime(true) - self::$runTime;
        self::write(LOG_INFO, $runtime, 'runtime');
        //页面执行超时时间
        $time_out = (float)Config::app('yaf.app.time_out');
        if ($runtime < $time_out) {
            return ;
        }
        $time_out = $runtime - $time_out;
        self::write(LOG_INFO, $time_out, ['line'=>'time out']);//记录超时时间
    }

    /**
     * @desc 记录内存的峰值
     */
    public static function memory()
    {
        $memory = memory_get_peak_usage();
        $memory = round($memory / 1048576, 2);
        self::write(LOG_INFO, "{$memory}MB", ['line'=>'memory']);//记录超时时间
    }

    /**
     * @desc
     */
    public static function _shutdown_function()
    {
    }

    /**
     * @desc 错误处理函数
     * @param int $errno
     * @param string $errstr
     * @param string $errfile
     * @param int $errline
     */
    public static function _error_handler(int $errno, string $errstr, string $errfile = null, int $errline = null)
    {
        //显示错误页面
        self::view('error', [
            'code' => $errno,
            'message' => $errstr,
            'line' => $errline,
            'file' => $errfile
        ]);
        self::write(LOG_ERR, "{$errno}:{$errstr}", ['line'=>$errline, 'file'=>$errfile]);//记录错误日志
    }

    /**
     * @desc 异常函数
     * @param Throwable $e
     */
    public static function _exception_handler(Throwable $e)
    {
        //显示错误页面
        self::view(500, [
            'code' => $e->getCode(),
            'message' => $e->getMessage(),
            'line' => $e->getLine(),
            'file' => $e->getFile(),
            'trace' => $e->getTraceAsString()
        ]);
        self::write(LOG_ERR, $e);//记录错误日志
    }

    /**
     * @desc view
     * @param $page
     * @param array $params
     */
    private static function view($page, $params = [])
    {
        if(PHP_SAPI === 'cli' || is_string($page)){
            return ;
        }
        extract($params);
        $_404Codes = [\YAF\ERR\NOTFOUND\MODULE, \YAF\ERR\NOTFOUND\CONTROLLER, \YAF\ERR\NOTFOUND\ACTION];//404错误码
        $errors = Config::app('yaf.app.errors');//获取配置信息
        $debug = Fun::get($errors, 'debug', true);//是否开启debug
        $_404 = Fun::get($errors, '_404');
        $_500 = Fun::get($errors, '_500');
        if (!$debug && in_array($params['code'], $_404Codes) && !empty($_404)) {//找不到页面
            $page = $errors['_404'];
        } else if (!$debug && !empty($_500)) {//500错误
            $page =  $errors['_500'];
        }
        //开启debug模式、执行系统错误页面否则执行app错误页面
        $basePath = $debug ? str_replace('Base', 'Error', dirname(__FILE__)) : APP_PATH;
        $page = $basePath.'/'.$page.'.phtml';//错误页面地址
        //指定了自定义错误页面
        file_exists($page) and include $page;
    }

    /**
     * @desc 记录最后的错误
     */
    public static function recordLastError()
    {
        register_shutdown_function(['\Core\Base\Log', '_shutdown_function']);
        set_error_handler(['\Core\Base\Log', '_error_handler']);
        set_exception_handler(['\Core\Base\Log', '_exception_handler']);
    }

    /**
     * @desc 运行时间
     * @var int
     */
    private static $runTime = 0;

    /**
     * @desc 设置开始时间
     */
    public static function startTime()
    {
        self::$runTime = microtime(true);
    }
}