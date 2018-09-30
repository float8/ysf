<?php
/**
 * @desc:socketIO 启动服务
 * @author: wanghongfeng
 * @date: 2017/11/6
 * @time: 下午5:36
 */

namespace Swoole\SocketIO;

use Core\Base\Log;
use Core\Utils\Tools\Fun;

class Server
{
    /**
     * @desc 启动 websocketd
     */
    public static function start(){
        self::debug();//开启关闭debug
        Log::recordLastError();//记录错误日志
        $host = Fun::config('webim.env.server');//服务信息
        $server = new \Swoole\SocketIO\Websocket\Server($host['host'], $host['port']);//websocket服务
        $server->run();
    }

    /**
     * @desc 开启/关闭 debug
     */
    private static function debug(){
        $project = Fun::config('project.env.project');
        if($project['debug']){//开始debug模式
            ini_set('display_errors', 'on');
            $level = isset($project['error_level'] ) ? $project['error_level'] : E_ALL;
            error_reporting($level);
            return ;
        }
        ini_set('display_errors', 'off');
        error_reporting(0);
    }
}