<?php
/**
 * @desc:
 * @author: wanghongfeng
 * @date: 2018/11/1
 * @time: 下午4:40
 */

namespace RealTime\Engine\SocketIO\Parser;

class Json extends Base
{
    /**
     * @desc 数据包类型
     * @var array
     */
    public $types = ['CONNECT', 'DISCONNECT', 'EVENT', 'ACK', 'ERROR', 'BINARY_EVENT', 'BINARY_ACK'];

    /**
     * Packet type `connect`.
     *
     * @api public
     */
    const CONNECT = 0;

    /**
     * Packet type `disconnect`.
     *
     * @api public
     */
    const DISCONNECT = 1;

    /**
     * Packet type `event`.
     *
     * @api public
     */
    const EVENT = 2;

    /**
     * Packet type `ack`.
     *
     * @api public
     */
    const ACK = 3;

    /**
     * Packet type `error`.
     *
     * @api public
     */
    const ERROR = 4;

    /**
     * Packet type 'binary event'
     *
     * @api public
     */
    const BINARY_EVENT = 5;

    /**
     * Packet type `binary ack`. For acks with binary arguments.
     *
     * @api public
     */
    const BINARY_ACK = 6;

    /**
     * @desc 错误数据包
     * @var array
     */
    private $errorPacket = [
        'type'=>self::ERROR,
        'data'=>'parser error'
    ];

    /**
     * @desc 解码
     * @param $packet
     * @return mixed
     */
    public function decode($packet)
    {
        $packet = json_decode($packet, true);
        return is_array($packet) ? $packet : $this->errorPacket;
    }

    /**
     * @desc 编码
     * @param $packet
     * @return string
     */
    public function encode($packet)
    {
        return json_encode($packet);
    }

    /**
     * @desc 错误
     * @param $msg
     * @return array
     */
    private function error($msg)
    {
        return ['type' => self::ERROR, 'data' => 'parser error: ' . $msg];
    }

}