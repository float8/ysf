<?php
/**
 * @desc:
 * @author: wanghongfeng
 * @date: 2018/10/25
 * @time: 上午10:56
 */

namespace RealTime\Protocol;

use Core\Base\Config;
use RealTime\Base\Command;

define('__APP_DIR__' , Config::app('swoole.directory'));//app dir

class Protocol
{
    /**
     * @desc 服务的类名
     * @var array
     */
    private static $serverClass = [
        'websocket' => '\RealTime\Protocol\Websocket\Server',
        'websocketClient' => 'Websocket\Server',
        'socket' => 'Websocket\Server',
        'socketClient' => 'Websocket\Server',
    ];

    /**
     * @var Websocket\Server|Websocket\Client|Socket\Client|Socket\Server $server
     */
    private static $server;

    /**
     * @desc 启动服务
     */
    public static function start()
    {
        self::server();//获取服务
        self::set();//设置配置选项
        self::setProcessTitle();//设置进程标题
        self::callMethod('on');//监听事件
        self::callMethod('start');//启动服务
    }

    /**
     * @desc 调用方法
     * @param $method
     * @return mixed
     */
    public static function callMethod($method)
    {
        return call_user_func([self::$server, $method]);//启动服务
    }

    /**
     * @desc 设置配置选项
     */
    public static function set()
    {
        $sets = Config::app('swoole.set', []);//获取配置选项
        $sets['daemonize'] = Command::getDaemonize();//守护进程化
        self::$server->server->set($sets);//添加swoole服务的设置
    }

    /**
     * @desc 设置进程标题
     */
    public static function setProcessTitle()
    {
        $process_title = Config::app('app.server.process_title');
        cli_set_process_title($process_title);//设置进程名称
    }

    /**
     * @desc 获取服务
     */
    private static function server()
    {
        $name = Config::app('app.server.name', 'websocket');//获取服务名称
        isset(self::$serverClass[$name]) or die('There is no service!');
        $className = self::$serverClass[$name];//服务名称
        $client = Config::app('app.server.client', 0);//是否为客户端
        $client and $className .= 'Client';
        $config = Config::app('app.server.config', []);//获取服务配置信息
        self::$server =  new $className($config);//实例化服务
    }
}