<?php
/**
 * @desc:
 * @author: wanghongfeng
 * @date: 2017/11/20
 * @time: 下午1:55
 */

namespace Swoole\SocketIO\Websocket\Event;

use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\SocketIO\Handshake;
use Swoole\SocketIO\Websocket\Parser\Decoder;

trait Worker
{
    /**
     * @desc 此事件在Worker进程/Task进程启动时发生。这里创建的对象可以在进程生命周期内使用
     * @return $this
     */
    private function onWorkerStart() {
        $event = $this->isUserEvent('workerStart');
        if(!$event){
            return $this;
        }
        $this->server->on('WorkerStart', function (\Swoole\WebSocket\Server $server, int $worker_id) use($event) {
            $this->onUserEvent($event, ['swoole'=>$server],['worker_id'=>$worker_id]);
        });
        return $this;
    }

    /**
     * @desc http请求
     */
    private function onRequest() {
        $this->server->on('request', function (Request $request, Response $response) {
            $http = new \Swoole\SocketIO\Websocket\Http\Server($request, $response, $this, $this->server);
            $http->run();
        });
        return $this;
    }

    /**
     * @desc open事件
     */
    private function onOpen() {
        $this->server->on('open', function (\Swoole\WebSocket\Server $server, Request $request) {
            Handshake::websocket($request->fd, $server, $request);
        });
        return $this;
    }

    /**
     * @desc 消息事件
     */
    private function onMessage() {
        $this->server->on('message', function (\Swoole\WebSocket\Server $server, $frame) {
            $packet = Decoder::decodeString($frame->data);
            if(empty($packet)){
                return ;
            }
            if( isset($this->event[$packet['type']]) ) {//已知事件触发
                call_user_func(["\Swoole\SocketIO\Websocket\Event", 'on'.ucfirst($packet['type'])], $server, $frame, $this ,  $packet['data'] );
                return ;
            }
            $this->runUserEvent($packet['type'], $server, $frame->fd, $packet['data']); //执行用户函数
        });
        return $this;
    }

    /**
     * @desc 关闭事件
     */
    private function onClose() {
        $this->server->on('close', function (\Swoole\WebSocket\Server $server, $fd){
            $this->runUserEvent('close', $server, $fd); //执行用户函数
        });
        return $this;
    }

}