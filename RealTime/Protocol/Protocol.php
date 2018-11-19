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
use RealTime\Engine\Engine;

define('__APP_DIR__' , Config::app('swoole.directory'));//app dir

class Protocol
{
    /**
     * @desc 服务的类名
     * @var array
     */
    private $servers = [
        'websocket' => '\RealTime\Protocol\Websocket\\',
        'socket'=> '\RealTime\Protocol\Socket\\'
    ];

    /**
     * @var Websocket\Server|Websocket\Client|Socket\Client|Socket\Server $server
     */
    private $server;

    public function __construct()
    {
        $this->server();//获取服务
        $this->set();//设置配置选项
        $this->setProcessTitle();//设置进程标题
        $this->callMethod('on');//监听事件
        $this->callMethod('start');//启动服务
    }

    /**
     * @desc 获取服务
     */
    private function server()
    {
        $name = Config::app('app.server.name', 'websocket');//获取服务名称
        isset($this->servers[$name]) or die('There is no service!');
        $server = $this->servers[$name];//服务名称
        $client = Config::app('app.server.client', 0);//是否为客户端
        $server .= $client ? 'Client' : 'Server';
        $config = Config::app('app.server.config', []);//获取服务配置信息
        $this->server =  new $server($config);//实例化服务
        Engine::init($this->server->server);//初始化引擎
    }

    /**
     * @desc 设置配置选项
     */
    private function set()
    {
        $sets = ['daemonize' => Command::getDaemonize()];//守护进程化
        $sets += Config::app('swoole.set', []);//获取配置选项
        $sets = method_exists($this->server, 'set') ?
                $this->server->set($sets) :
                $sets;
        $this->server->server->set($sets);//添加swoole服务的设置
    }

    /**
     * @desc 设置进程标题
     */
    private function setProcessTitle()
    {
        cli_set_process_title(Config::app('app.server.process_title'));//设置进程名称
    }

    /**
     * @desc 调用方法
     * @param $method
     * @return mixed
     */
    private function callMethod($method)
    {
        return call_user_func([$this->server, $method]);//启动服务
    }
}