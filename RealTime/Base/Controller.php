<?php
/**
 * @desc:
 * @author: wanghongfeng
 * @date: 2018/11/8
 * @time: 下午8:13
 */

namespace RealTime\Base;

abstract class Controller
{
    /**
     * @var \RealTime\Engine\SocketIO\Emitter
     */
    public $emitter;

    public $route;

    public $actions = [];

    /**
     * @desc 发送消息
     * @param $event
     * @return mixed
     */
    public function emit($event)
    {
        $this->emitter and call_user_func_array([$this->emitter, 'emit'], func_get_args());
        return $this;
    }

    /**
     * @desc emit error
     * @param $data
     */
    public function emitError($data)
    {
        $this->emitter and call_user_func([$this->emitter, 'emitError'], $data);
        return $this;
    }

    /**
     * @desc 指向的链接
     * @param $fd
     * @return $this
     */
    public function to($fd)
    {
        $this->emitter and  $this->emitter->to($fd);
        return $this;
    }

}