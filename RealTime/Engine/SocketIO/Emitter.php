<?php
/**
 * @desc:
 * @author: wanghongfeng
 * @date: 2018/11/2
 * @time: 下午3:56
 */

namespace RealTime\Engine\SocketIO;

class Emitter
{
    /**
     * @var int 当前连接
     */
    private $fd;

    /**
     * @var int 发送的链接
     */
    private $toFd;

    /**
     * @var \RealTime\Engine\SocketIO\Engine 引擎
     */
    private $engine;

    /**
     * @var array send data
     */
    private $writeBuffer = [];

    /**
     * @var string namespace
     */
    private $nsp;

    public function __construct($engine, $fd)
    {
        $this->fd = $fd;
        $this->engine = $engine;
    }

    /**
     * @desc namespace
     * @param $nsp
     */
    public function setNsp($nsp)
    {
        $this->nsp = $nsp;
    }

    /**
     * @desc 获取连接编号
     * @return int
     */
    private function getFd()
    {
        $fd = $this->toFd ?: $this->fd;
        $this->toFd = null;
        return $fd;
    }

    /**
     * @desc 目标链接
     * @param $fd
     */
    public function to($fd)
    {
        $this->toFd = $fd;
    }

    /**
     * @desc 发送消息
     * @param $event
     */
    public function emit($event)
    {
        $this->writeBuffer('message', [
            'type' => $this->engine->parser::EVENT,
            'nsp' => $this->nsp,
            'data' => func_get_args()
        ]);
    }

    /**
     * @desc 发送错误信息
     * @param $data
     */
    public function emitError($data)
    {
        $this->writeBuffer('message', [
            'type' => $this->engine->parser::ERROR,
            'nsp' => $this->nsp,
            'data' => $data
        ]);
    }

    /**
     * @param $type
     * @param $data
     */
    public function writeBuffer($type, $data = null)
    {
        $data = $this->engine->packet($type, $data);
        $this->writeBuffer[] = [
            'fd' => $this->getFd(),
            'data' => $data
        ];
        return $this;
    }

    /**
     * @desc 发送消息
     * @param $fd
     * @param $data
     * @return mixed
     */
    public function send($fd, $data)
    {
        if(!$this->engine->server->exist($fd) || empty($data)){
            return false;
        }
        $method = method_exists($this->engine->server, 'push') ? 'push' : 'send';
        return call_user_func([$this->engine->server, $method], $fd, $data);
    }

    /**
     * @desc 发送数据
     * @return $this
     */
    public function flush()
    {
        if (empty($this->writeBuffer)) {
            return $this;
        }
        $writeBuffer= $this->writeBuffer;
        $this->writeBuffer = [];
        foreach ($writeBuffer as $data) {
            $this->send($data['fd'], $data['data']);
        }
        return $this;
    }
}