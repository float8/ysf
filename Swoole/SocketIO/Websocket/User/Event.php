<?php
/**
 * @desc:
 * @author: wanghongfeng
 * @date: 2017/11/2
 * @time: 下午5:35
 */

namespace Swoole\SocketIO\Websocket\User;

use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\SocketIO\Websocket\Server;

abstract class Event
{

    /**
     * @see Request
     * @var Request
     */
    private $request = null;

    /**
     * @see Response
     * @var Response
     */
    private $response = null;

    /**
     * @see Server
     * @var Server
     */
    private $server = null;

    /**
     * @var int
     */
    private $fd = 0;

    /**
     * @var  mixed
     */
    private $data = null;

    /**
     * @var \Swoole\WebSocket\Server
     */
    private $swoole = null;


    /**
     * @desc 运行方法
     * @return mixed
     */
    abstract public function execute();
    /**
     * @return Request
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * @param Request $request
     */
    public function setRequest(Request $request)
    {
        $this->request = $request;
    }

    /**
     * @return Response
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * @param Response $response
     */
    public function setResponse(Response $response)
    {
        $this->response = $response;
    }

    /**
     * @return Server
     */
    public function getServer()
    {
        return $this->server;
    }

    /**
     * @param Server $server
     */
    public function setServer($server)
    {
        $this->server = $server;
    }

    /**
     * @return int
     */
    public function getFd(): int
    {
        return $this->fd;
    }

    /**
     * @param int $fd
     */
    public function setFd(int $fd)
    {
        $this->fd = $fd;
    }

    /**
     * @return mixed
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param mixed $data
     */
    public function setData($data)
    {
        $this->data = $data;
    }

    /**
     * @return \Swoole\WebSocket\Server
     */
    public function getSwoole()
    {
        return $this->swoole;
    }

    /**
     * @param \Swoole\WebSocket\Server $swoole
     */
    public function setSwoole(\Swoole\WebSocket\Server $swoole)
    {
        $this->swoole = $swoole;
    }

}