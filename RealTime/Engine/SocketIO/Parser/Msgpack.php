<?php
/**
 * @desc:
 * @author: wanghongfeng
 * @date: 2018/11/1
 * @time: 下午4:40
 */

namespace RealTime\Engine\SocketIO\Parser;


class Msgpack
{
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
        try {
            return msgpack_unpack($packet);
        } catch (\Exception $e) {
            return $this->errorPacket;
        }
    }

    /**
     * @desc 编码
     * @param $packet
     * @return string
     */
    public function encode($packet)
    {
        try {
            switch ($packet['type']) {
                case self::CONNECT:
                case self::DISCONNECT:
                case self::ERROR:
                    return json_encode($packet);
                default:
                    return msgpack_pack($packet);
            }
        } catch (\Exception $e) {
            return json_encode($this->errorPacket);
        }
    }

}