<?php
/**
 * @desc:
 * @author: wanghongfeng
 * @date: 2018/11/7
 * @time: 下午7:47
 */

namespace RealTime\Engine\SocketIO\Engine;


use Core\Utils\Tools\Fun;

trait Event
{
    /**
     * @desc 命名空间
     * @var string
     */
    private $nsp = '/';

    /**
     * @desc 监听数据包
     * @param $data
     * @param $callable
     */
    private function _onPacket($data, $callable)
    {
        try {
            $packet = $this->decodePacket($data);//解包
            switch ($packet['type'])
            {
                case 'message':
                    $this->_onMessage($packet['data'], $callable);
                    break;
                case 'ping':
                    $this->sendPacket('pong');
                    break;
            }
        } catch (\Exception $e) {

        }
    }

    /**
     * @desc 消息
     * @param $data
     * @param $callable
     */
    private function _onMessage($data, $callable)
    {
        $packet = $this->parser->decode($data);//解码
        $this->nsp = $packet['nsp'];
        switch ($packet['type'])
        {
            case $this->parser::CONNECT:
                $this->_onConnect($callable);
                break;
            case $this->parser::EVENT:
                $this->_onEvent($packet, $callable);
                break;
        }
    }

    /**
     * @desc 引擎链接事件
     * @param $callable
     */
    private function _onConnect($callable)
    {
        $params = $this->routeEventParser();//路由解析器
        $this->_verifyModule($params['module']);//验证
        //发送包数据
        $this->sendPacket('message',
            $this->parser->encode([
                'type' => $this->parser::CONNECT,
                'nsp' => $this->nsp
            ])
        );
        $params['engine'] = $this;
        call_user_func($callable, 'connect', $params);
    }

    /**
     * @desc 事件处理
     * @param $packet
     * @param $callable
     */
    private function _onEvent($packet, $callable)
    {
        $event = Fun::get($packet['data'], '0');//事件名称
        $this->_verifyEvent($event);//验证evnet是否合法
        $params = $this->routeEventParser($event);//路由解析器
        $this->_verifyModule($params['module']);//验证
        $params['data'] = array_slice($packet['data'], 1);//数据
        $params['engine'] = $this;
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
        $this->sendErrorPacket('Invalid event name');//发送错误包数据
    }

    /**
     * @desc 验证模块是否存在
     * @param $module
     */
    private function _verifyModule($module)
    {
        if(isset($this->modules[$module])) {
            return;
        }
        $this->sendErrorPacket('Invalid namespace');//发送错误包数据
    }

}