<?php
/**
 * @desc:
 * @author: wanghongfeng
 * @date: 2018/10/25
 * @time: 上午11:08
 */

namespace RealTime\Protocol\Websocket;

use RealTime\Base\Loader;
use RealTime\Engine\Engine;

class Server
{
    /**
     * @desc websocket 事件
     * @var array
     */
    private $events = [
        //Master进程内的回调函数
        'Start',
        'Shutdown',
        //Worker进程内的回调函数
        'Connect',
        'WorkerStart',
        'WorkerStop',
        'WorkerExit',
        'WorkerError',
        'Close',
        'Finish',
        'Packet',
        'BufferFull',
        'BufferEmpty',
        'PipeMessage',
        //websocket
        'Handshake',
        'Open',
        'Request',
        'Message',
        //task_worker进程内的回调函数
        'Task',
        //Manager进程内的回调函数
        'ManagerStart',
        'ManagerStop',
    ];

    /**
     * @desc 需要重置的事件
     * @var array
     */
    private $resetEvents = [
        'Connect' => 1,
        'Open' => 1,
        'Request' => 1,
        'Message' => 1,
    ];

    /**
     * @desc websocket server
     * @var \Swoole\WebSocket\Server
     */
    public $server;

    public function __construct($params)
    {
        $this->server = new \Swoole\WebSocket\Server($params['host'], $params['port']);//实例化swoole websocket服务
        Engine::init($this->server);//初始化引擎
    }

    /**
     * @desc 启动
     */
    public function start()
    {
        $this->server->start();//启动
    }

    /**
     * @desc 监听事件
     */
    public function on()
    {
        foreach ($this->events as $event) {
            //监听 reset event
            if (isset($this->resetEvents[$event])) {
                $this->server->on($event, function () use ($event) {
                    $params = func_get_args();
                    $emitter = Engine::on(strtolower($event), $params);//执行引擎的事件
                    $object = Loader::swoole($event) and
                    $object->emitter = $emitter and
                    call_user_func_array([$object, 'execute'], $params); //执行系统事件
                });
                continue;
            }
            //执行系统事件
            $object = Loader::swoole($event) and $this->server->on($event, function () use ($event, $object) {
                call_user_func_array([$object, 'execute'], func_get_args()); //执行系统事件
            });
        }
    }
}