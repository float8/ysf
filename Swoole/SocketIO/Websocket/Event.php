<?php
/**
 * @desc:
 * @author: wanghongfeng
 * @date: 2017/10/30
 * @time: 下午5:59
 */

namespace Swoole\SocketIO\Websocket;

use Swoole\SocketIO\Handshake;
use Swoole\WebSocket\Server;

class Event
{

    /**
     * @desc ask
     * @param Server $server
     * @param $frame
     * @param \Swoole\SocketIO\Websocket\Server $IOServer
     * @param $data
     */
    public static function onAsk(\Swoole\WebSocket\Server $server, $frame, \Swoole\SocketIO\Websocket\Server $IOServer, $data){

    }
    /**
     * @desc 当WebSocket客户端与服务器建立连接并完成握手后会回调此函数。
     * @param $fd
     * @param \Swoole\SocketIO\Websocket\Server $IOServer
     */
    public static function onOpen($fd, \Swoole\SocketIO\Websocket\Server $IOServer, \Swoole\WebSocket\Server $server){
        $IOServer->runUserEvent('open', $server, $fd); //执行用户函数
    }

    /**
     * @desc 心跳事件
     * @param Server $server
     * @param $frame
     * @param $IOServer
     * @param $data
     */
    public static function onPing(\Swoole\WebSocket\Server $server, $frame, \Swoole\SocketIO\Websocket\Server $IOServer, $data) {
        if($data == 'probe'){
            Handshake::websocket($frame->fd, $server);//socketIO socket消息握手
            return ;
        }
        //客户端返回 pong
        $IOServer->send($frame->fd, 'pong', '');
        //执行用户函数
        $IOServer->runUserEvent('ping', $server, $frame->fd);
    }

    /**
     * @desc socketIO Upgrade
     */
    public static function onUpgrade(\Swoole\WebSocket\Server $server, $frame, \Swoole\SocketIO\Websocket\Server $IOServer) {
        self::onOpen($frame->fd, $IOServer, $server);//当WebSocket客户端与服务器建立连接并完成握手后会回调此函数。
    }

}