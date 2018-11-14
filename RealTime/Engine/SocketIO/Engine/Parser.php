<?php
/**
 * @desc:
 * @author: wanghongfeng
 * @date: 2018/10/31
 * @time: 下午2:40
 */

namespace RealTime\Engine\SocketIO\Engine;

use Core\Utils\Tools\Fun;

trait Parser
{
    /**
     * @desc 数据包类型
     * @var array
     */
    private $types = [
        'open' => 0,
        'close' => 1,
        'ping' => 2,
        'pong' => 3,
        'message' => 4,
        'upgrade' => 5,
        'noop' => 6,
    ];

    /**
     *  数据包类型的反转
     * @var array
     */
    private $typesReverse = [];

    /**
     * @desc 错误包信息
     * @var array
     */
    private $err = ['type' => 'error', 'data' => 'parser error'];

    /**
     * @desc 编码
     * @param $packet
     * @return mixed
     */
    private function encodePacket($packet)
    {
        $type = Fun::get($packet, 'type');
        $data = Fun::get($packet, 'data');
        // Sending data as a utf-8 string
        $encoded = strval($this->types[$type]);
        if (isset($data[0]) && !is_numeric($data[0]) &&$data[0] != '{') {
            $encoded = chr($encoded);
        }
        // data fragment is optional
        if ($data !== null) {
            $encoded .= strval($data);
        }
        return $encoded;
    }


    /**
     * @desc 解码
     * @param $data
     * @return mixed
     */
    private function decodePacket($data)
    {
        if (empty($data)) {
            return $this->err;
        }
        // binary data
        if ($this->isBuff($data[0])) {
            return strlen($data) == 1 ?
                ['type' => $this->typesReverse[ord($data[0])]] :
                ['type' => $this->typesReverse[ord($data[0])], 'data' => substr($data, 1)];
        }
        // String data error
        if (is_string($data) && (!is_numeric($data[0]) || !isset($this->typesReverse[$data[0]]))) {
            return $this->err;
        }

        // String data
        if (is_string($data)) {
            return strlen($data) == 1 ?
                ['type' => $this->typesReverse[$data[0]]] :
                ['type' => $this->typesReverse[$data[0]], 'data' => substr($data, 1)];
        }
    }

    /**
     * @desc 判断是否为二进制数据
     * @param $data
     * @return bool
     */
    private function isBuff($type)
    {
        if(!isset($this->typesReverse[$type]) && isset($this->typesReverse[ord($type)])){
            return true;
        }
        return false;
    }
}