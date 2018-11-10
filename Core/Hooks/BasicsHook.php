<?php
/**
 * @desc: 基础钩子
 * @author: wanghongfeng
 * @date: 2018/10/24
 * @time: 下午9:57
 */

namespace Core\Hooks;


use Core\Base\Config;
use Core\Utils\Tools\Fun;

class BasicsHook
{
    /**
     * @desc 写日志
     * @param $params
     * @return bool
     */
    public static function logWrite($params)
    {
        $log_local = Config::app('app.log_local');//syslog log_local
        $line = Fun::get($params, 'trace.line');//跟踪的行号
        $file = Fun::get($params, 'trace.file');//跟踪的文件
        $message = Fun::get($params, 'message');//日志内容
        $logid = Fun::get($params, 'logid');//日志编号
        $level = Fun::get($params, 'level');//日志登记

        $location = empty($file) ? "[{$line}]" : "[{$line}:{$file}]";//位置
        $message = empty($traceString) ? $message : $message."\n".$traceString;
        $message = date("Y-m-d H:i:s") . " {$logid} " . PROJECT_NAME . " {$location} {$message}";//消息

        return syslog($level | $log_local, $message);//写入系统日志
    }

}