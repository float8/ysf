<?php
/**
 * @desc:
 * @author: wanghongfeng
 * @date: 2018/10/31
 * @time: 下午2:36
 */

namespace RealTime\Engine\SocketIO;

use Core\Base\Config;
use Core\Utils\Tools\Fun;
use RealTime\Engine\SocketIO\Engine\Event;
use RealTime\Engine\SocketIO\Engine\Route;
use RealTime\Engine\SocketIO\Engine\Swoole;
use RealTime\Engine\SocketIO\Engine\Parser;

class Engine
{
    use Parser;//解析
    use Event;//框架事件
    use Swoole;//swoole
    use Route;//路由

    /**
     * @desc 握手
     * @var Handshake
     */
    private $handshake;

    /**
     * @var \Swoole\Server|\Swoole\WebSocket\Server
     */
    public $server;

    /**
     * @desc 解析器
     * @var array
     */
    private $parsers = [
        'socketio'=>'\RealTime\Engine\SocketIO\Parser\SocketIO',
        'msgpack'=>'\RealTime\Engine\SocketIO\Parser\Msgpack',
        'json'=>'\RealTime\Engine\SocketIO\Parser\Json',
    ];

    /**
     * @var \RealTime\Engine\SocketIO\Parser\SocketIO|\RealTime\Engine\SocketIO\Parser\Msgpack|\RealTime\Engine\SocketIO\Parser\Json
     */
    public $parser;

    /**
     * @desc 模块
     * @var array
     */
    private $modules;

    /**
     * @desc 配置信息
     * @var array
     */
    private $config;

    public function __construct($server, $modules)
    {
        $this->config = Config::app('app.server.socketio');//获取socketio配置信息
        $parser = Fun::get($this->config, 'parser', 'socketio');//获取绑定的解析器
        isset($this->parsers[$parser]) or die('The parser does not exist');
        $this->server = $server;
        $this->modules = $modules;
        $this->typesReverse = array_flip($this->types);
        $this->handshake = new Handshake($this, $this->config);//实例化握手
        $this->parser = new $this->parsers[$parser]();//实例化解析器
    }

    /**
     * @desc 发送器
     * @param $fd
     * @return Emitter
     */
    private function emitter($fd)
    {
        return new Emitter($this, $fd);
    }

    /**
     * @desc 包
     * @param $type
     * @param $data
     * @param string $nsp
     * @return string
     */
    public function packet($type, $data = null, $options = [])
    {
        $options['compress'] = Fun::get($options, 'compress', true);
        $data = is_string($data) ? $data : $this->parser->encode($data);;
        $encode = $this->encodePacket([
            'type' => $type,
            'data'=>$data,
            'options' => $options
        ]);//编码
        return $encode;
    }
}