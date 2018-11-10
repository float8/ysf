<?php
/**
 * @desc:
 * @author: wanghongfeng
 * @date: 2018/11/7
 * @time: 上午11:32
 */

namespace RealTime\Engine\SocketIO\Engine;

use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\WebSocket\Frame;
use Swoole\Server;

trait Swoole
{
    /**
     * @desc 有新的连接进入时，在worker进程中回调。
     * @param Server $server
     * @param int $fd
     * @param int $reactorId
     * @return mixed
     */
    public function onConnect(Server $server, int $fd, int $reactorId)
    {
        $emitter = $this->emitter($fd);
        return $emitter->flush();
    }


    /**
     * @desc 接收到数据时回调此函数，发生在worker进程中。
     * @param Server $server
     * @param int $fd
     * @param int $reactor_id
     * @param string $data
     * @param $callable
     * @return mixed
     */
    public function onReceive(Server $server, int $fd, int $reactor_id, string $data, $callable)
    {
        $emitter = $this->emitter($fd);
        return $emitter->flush();
    }

    /**
     * @desc 当WebSocket客户端与服务器建立连接并完成握手后会回调此函数。
     * @param \Swoole\WebSocket\Server $server
     * @param Request $request
     * @return mixed
     */
    public function onOpen(\Swoole\WebSocket\Server $server, Request $request)
    {
        $emitter = $this->emitter($request->fd);
        $this->handshake->onOpen($emitter);//握手
        return $emitter->flush();
    }

    /**
     * @desc 当服务器收到来自客户端的数据帧时会回调此函数。
     * @param \Swoole\WebSocket\Server $server
     * @param Frame $frame
     * @param $callable
     * @return mixed
     */
    public function onMessage(\Swoole\WebSocket\Server $server, Frame $frame, $callable)
    {
        $emitter = $this->emitter($frame->fd);
        $params = array_slice(func_get_args(), 0, -1);
        $this->_onPacket($frame->data, $emitter, function ($event, $data) use($callable, $params, $emitter) {
            call_user_func_array($callable, array_merge([$event ,$emitter, $data], $params));
        });
        return $emitter->flush();
    }

    /**
     * @desc 在收到一个完整的Http请求后，会回调此函数。
     * @param Request $request
     * @param Response $response
     * @param $callable
     * @return mixed
     */
    public function onRequest(Request $request, Response $response, $callable)
    {
        $emitter = $this->emitter($request->fd);
        $route = $this->routeWebParser($request->server['path_info']);
        $route['engine'] = $this;
        $params = array_slice(func_get_args(), 0, -1);
        call_user_func_array($callable, array_merge([$emitter, $route], $params));
        return $emitter->flush();
    }


}