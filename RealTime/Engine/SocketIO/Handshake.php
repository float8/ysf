<?php
/**
 * @desc: 握手
 * @author: wanghongfeng
 * @date: 2018/10/31
 * @time: 下午2:47
 */

namespace RealTime\Engine\SocketIO;

use Core\Utils\Tools\Fun;

class Handshake
{
    /**
     * @desc ping 的时间间隔
     * @var int
     */
    private $pingInterval = 25000;

    /**
     * @desc ping 超时时间
     * @var int
     */
    private $pingTimeout = 25000;

    /**
     * @var Engine
     */
    private $engine;

    public function __construct(Engine $engine, $config)
    {
        $this->engine = $engine;
        $this->pingInterval = Fun::get($config, 'pingInterval', 25000);
        $this->pingTimeout = Fun::get($config, 'pingTimeout', 5000);
    }

    /**
     * @desc 生成会话编号
     * @return string
     */
    private function sid()
    {
        $pack1 = pack('d', microtime(true));
        $pack2 = pack('N', random_int(1, 100000000));
        return bin2hex( $pack1.$pack2 );
    }

    /**
     * @desc handshake
     * @param Emitter $emitter
     * @param string $upgrades
     */
    public function on($emitter, $upgrades)
    {
        $emitter->writeBuffer('open', json_encode([//config info
                'sid' => $this->sid(),
                'upgrades'=> [$upgrades],
                'pingInterval'=>$this->pingInterval,
                'pingTimeout'=>$this->pingTimeout
            ])
        );
        $emitter->writeBuffer('message', [//namespace /
                'type' => $this->engine->parser::CONNECT
        ]);
    }

}