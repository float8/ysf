<?php
/**
 * @desc:
 * @author: wanghongfeng
 * @date: 2018/11/7
 * @time: 下午7:47
 */

namespace RealTime\Engine\SocketIO\Engine;

use Core\Utils\Tools\Fun;
use RealTime\Engine\SocketIO\Emitter;

trait Event
{
    /**
     * @desc 监听数据包
     * @param string $data
     * @param Emitter $emitter
     * @param callable $callable
     */
    private function _onPacket($data, $emitter, $callable)
    {
        $packet = $this->decodePacket($data);//解包
        try {
            switch ($packet['type'])
            {
                case 'message':
                    $this->_onMessage($emitter, $packet['data'], $callable);
                    break;
                case 'ping':
                    $emitter->writeBuffer('pong');
                    break;
            }
        } catch (\Exception $e) {
            $emitter->emitError($e->getMessage());//发送错误包数据
        }
    }

    /**
     * @desc 消息
     * @param Emitter $emitter
     * @param string $data
     * @param callable $callable
     */
    private function _onMessage($emitter, $data, $callable)
    {
        $packet = $this->parser->decode($data);//解码
        $emitter->setNsp($packet['nsp']);//设置 namespace
        switch ($packet['type'])
        {
            case $this->parser::CONNECT:
                $this->_onConnect($emitter, $packet['nsp'], $callable);
                break;
            case $this->parser::EVENT:
                $this->_onEvent($packet, $callable);
                break;
        }
    }

    /**
     * @desc 引擎链接事件
     * @param Emitter $emitter
     * @param string $nsp
     * @param callable $callable
     */
    private function _onConnect($emitter, $nsp, $callable)
    {
        $params = $this->routeEventParser($nsp);//路由解析器
        $this->_verifyModule($params['module']);//验证
        $emitter->writeBuffer('message', [
            'type' => $this->parser::CONNECT,
            'nsp' => $nsp
        ]);//发送包数据
        call_user_func($callable, 'connect', $params);
    }

    /**
     * @desc 事件处理
     * @param array $packet
     * @param callable $callable
     */
    private function _onEvent($packet, $callable)
    {
        $event = Fun::get($packet['data'], '0');//事件名称
        $this->_verifyEvent($event);//验证evnet是否合法
        $params = $this->routeEventParser($packet['nsp'], $event);//路由解析器
        $this->_verifyModule($params['module']);//验证
        $params['data'] = array_slice($packet['data'], 1);//数据
        call_user_func($callable, 'event', $params);
    }

    /**
     * @desc 验证事件是否合法
     * @param $event
     */
    private function _verifyEvent($event)
    {
        if (empty($event) || preg_match("/^[\w ]+$/", $event)) {//验证事件名称
            return ;
        }
        new \Exception('Invalid event name');
    }

    /**
     * @desc 验证模块是否存在
     * @param string $module
     */
    private function _verifyModule($module)
    {
        if(isset($this->modules[$module])) {
            return;
        }
        new \Exception('Invalid namespace');
    }

}