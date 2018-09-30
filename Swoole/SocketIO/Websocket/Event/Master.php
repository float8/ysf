<?php
/**
 * @desc:
 * @author: wanghongfeng
 * @date: 2017/11/20
 * @time: 下午1:52
 */

namespace Swoole\SocketIO\Websocket\Event;


trait Master
{
    /**
     * @desc Server启动在主进程的主线程回调此函数，函数原型
     */
    private function onStart(){
        if($event = $this->isUserEvent('start')){
            $this->server->on('start', function (\Swoole\WebSocket\Server $server) use ($event){
                $this->onUserEvent($event, $server);
            });
        }
    }
}