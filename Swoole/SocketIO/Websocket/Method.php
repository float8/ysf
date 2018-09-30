<?php
/**
 * @desc:
 * @author: wanghongfeng
 * @date: 2017/11/6
 * @time: 上午11:36
 */

namespace Swoole\SocketIO\Websocket;


trait Method
{
    /**
     * @desc 发送事件
     * @param $fd
     * @param $eventname
     * @param $data
     * @return bool
     */
    public function emit($fd, $eventname, $data = null){
        $msg = [$eventname];
        if(!is_null($data)){
            $msg[] = $data;
        }
        return $this->send($fd, 'message', \Swoole\SocketIO\Websocket\Parser\Parser::EVENT, $msg);
    }

    /**
     * @desc 发送数据
     * @param $fd
     * @param $packet
     * @param $packetType
     * @param $msg
     * @return bool
     */
    public function send($fd, $packet, $packetType, $msg = null) {
        $packets = \Swoole\SocketIO\Parser::$packets;
        $data = $packets[$packet].$packetType.(empty($msg) ? '' : json_encode($msg));
        if(!$this->server->exist($fd)){
            return false;
        }
        return $this->server->push($fd, $data);
    }

}