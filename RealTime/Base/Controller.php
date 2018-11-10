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
    public $engine;

    public $route;

    public $actions = [];

    /**
     * @desc 发送消息
     * @param $event
     * @return mixed
     */
    public function emit($event)
    {
        return call_user_func_array([$this->engine, 'emit'], func_get_args());
    }

    /**
     * @desc emit error
     * @param $data
     * @return mixed
     */
    public function emitError($data)
    {
        return call_user_func([$this->engine, 'emitError'], $data);
    }

    /**
     * @desc 指向的链接
     * @param $fd
     * @return mixed
     */
    public function to($fd)
    {
        return $this->engine->to($fd);
    }

}