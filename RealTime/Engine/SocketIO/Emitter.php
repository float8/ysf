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
    private $defaultNsp = '/';

    private $nsp = null;

    /**
     * @var \RealTime\Engine\SocketIO\Parser\SocketIO|\RealTime\Engine\SocketIO\Parser\Msgpack|\RealTime\Engine\SocketIO\Parser\Json
     */
    public $parser;

    public function __construct($parser)
    {
        $this->parser = $parser;
    }

    public function to($fd)
    {

    }

    public function emit($eventname)
    {
        //2 encode
        //4 message

    }


    public function packet($packet)
    {
        $sameNamespace = $packet['nsp'] === $this->nsp;
        $rootNamespaceError = $packet['type'] === $this->parser::ERROR && $packet['nsp'] === $this->defaultNsp;

        if (!$sameNamespace && !$rootNamespaceError) {
            return ;
        }

        switch ($packet['type']) {
            case $this->parser::CONNECT:
                $packet['nsp'] = '';
                this.onconnect();
                break;

            case $this->parser::EVENT:
                this.onevent(packet);
                break;

            case $this->parser::BINARY_EVENT:
                this.onevent(packet);
                break;

            case $this->parser::ACK:
                this.onack(packet);
                break;

            case $this->parser::BINARY_ACK:
                this.onack(packet);
                break;

            case $this->parser::DISCONNECT:
                this.ondisconnect();
                break;

            case $this->parser::ERROR:
                this.emit('error', packet.data);
                break;
        }
    }

//this.io = io;
//this.nsp = nsp;
//this.json = this; // compat
//this.ids = 0;
//this.acks = {};
//this.receiveBuffer = [];
//this.sendBuffer = [];
//this.connected = false;
//this.disconnected = true;
//this.flags = {};
//	  if (opts && opts.query) {
//          this.query = opts.query;
//      }
//	  if (this.io.autoConnect) this.open();


}