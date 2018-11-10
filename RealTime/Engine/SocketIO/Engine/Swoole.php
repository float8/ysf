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
     */
    public function onConnect(Server $server, int $fd, int $reactorId)
    {
        $this->_fd = $fd;
//        $this->handshake->onOpen();
    }

    /**
     * @desc 当WebSocket客户端与服务器建立连接并完成握手后会回调此函数。
     * @param \Swoole\WebSocket\Server $server
     * @param Request $request
     * @return bool
     */
    public function onOpen(\Swoole\WebSocket\Server $server, Request $request)
    {
        $this->_fd = $request->fd;
        $this->handshake->onOpen();//握手
    }


    /**
     * @desc 接收到数据时回调此函数，发生在worker进程中。
     * @param Server $server
     * @param int $fd
     * @param int $reactor_id
     * @param string $data
     */
    public function onReceive(Server $server, int $fd, int $reactor_id, string $data, $callable)
    {
        $this->_fd = $fd;
        $params = array_slice(func_get_args(), -1);
        $this->_onPacket($data, function ($event, $data = null) use($callable, $params) {
            call_user_func_array($callable, array_merge([$event, $data], $params));
        });
    }

    /**
     * @desc 当服务器收到来自客户端的数据帧时会回调此函数。
     * @param \Swoole\WebSocket\Server $server
     * @param Frame $frame
     */
    public function onMessage(\Swoole\WebSocket\Server $server, Frame $frame, $callable)
    {
        $this->_fd = $frame->fd;
        $params = array_slice(func_get_args(), 0, -1);
        $this->_onPacket($frame->data, function ($event, $data) use($callable, $params) {
            call_user_func_array($callable, array_merge([$event, $data], $params));
        });
    }

    /**
     * @desc 在收到一个完整的Http请求后，会回调此函数。
     * @param Request $request
     * @param Request $response
     */
    public function onRequest(Request $request, Response $response, $callable)
    {
        $this->_fd = $request->fd;
        $route = $this->routeWebParser($request->server['path_info']);
        $route['engine'] = $this;
        $params = array_slice(func_get_args(), 0, -1);
        call_user_func_array($callable, array_merge([$route], $params));
    }
}