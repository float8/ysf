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
    public $handshake;

    /**
     * @var \Swoole\Server|\Swoole\WebSocket\Server
     */
    public $server;

    /**
     * @desc 模板联接编号
     * @var int
     */
    public $fd;

    /**
     * @desc 当前联接编号
     * @var int
     */
    public $_fd;

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
    public $modules;

    /**
     * @desc 配置信息
     * @var array
     */
    public $config;

    public function __construct($server, $modules)
    {
        $this->config = Config::app('app.server.socketio');//获取socketio配置信息
        //获取绑定的解析器
        $parser = Fun::get($this->config, 'parser', 'socketio');
        if(!isset($this->parsers[$parser])){
            die('The parser does not exist');
        }
        $this->server = $server;
        $this->modules = $modules;
        $this->typesReverse = array_flip($this->types);
        $this->handshake = new Handshake($this, $this->config);//实例化握手
        $this->parser = new $this->parsers[$parser]();//实例化解析器
    }

    /**
     * @desc 编码
     * @param $type
     * @param $data
     * @param string $nsp
     * @return string
     */
    public function packet($type, $data = null, $nsp = '/')
    {
        $packet = ['type' => $type, 'data' => $data, 'nsp' => $nsp];
        $encode = $this->parser->encode($packet);
        return $encode;
    }

    /**
     * @desc 发送包
     * @param $type
     * @param $data
     * @param $options
     */
    public function sendPacket($type, $data = null, $options = [])
    {
        $options['compress'] = Fun::get($options, 'compress', true);
        $packet = ['type' => $type, 'data'=>$data, 'options' => $options];
        $encode = $this->encodePacket($packet);//编码
        $method = method_exists($this->server, 'push') ? 'push' : 'send';
        $fd = $this->fd ?: $this->_fd;//链接编号
        $this->fd = 0;//初始化
        return call_user_func([$this->server, $method], $fd, $encode);
    }

    /**
     * @desc send Error error
     * @param $data
     */
    public function sendErrorPacket($data)
    {
        $this->emitError($data);
        throw new \Exception($data);
    }

    /**
     * @desc emit error
     * @param $data
     * @return mixed
     */
    public function emitError($data)
    {
        return $this->sendPacket('message',
            $this->parser->encode([
                'type' => $this->parser::ERROR,
                'nsp' => $this->nsp,
                'data'=>$data
            ])
        );
    }

    /**
     * @desc 发送消息
     * @param $event
     */
    public function emit($event)
    {
        return $this->sendPacket('message',
            $this->parser->encode([
                'type' => $this->parser::EVENT,
                'nsp' => $this->nsp,
                'data'=>func_get_args()
            ])
        );
    }


    /**
     * @desc 目标链接
     * @param $fd
     */
    public function to($fd)
    {
        $this->fd = $fd;
    }

}