<?php
/**
 * @desc:
 * @author: wanghongfeng
 * @date: 2017/11/2
 * @time: 下午5:35
 */

namespace Swoole\SocketIO\Websocket\Http;

use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\SocketIO\Websocket\Server;

abstract class Action
{
    public function __construct()
    {
        ob_start();
    }

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

    /**
     * @desc 获取data数据
     * @return string
     */
    public function getData(){
        $data = $this->getRequest()->data;
        $data = explode("\r\n\r\n", $data);
        return isset($data[1]) ? $data[1] : '';
    }

    /**
     * @desc 获取data数组
     * @return array
     */
    public function getDataArray() {
        $data = $this->getData();
        if(empty($data)){
            return [];
        }
        $data = json_decode($data, true);
        return is_array($data) ? $data : [];
    }

    /**
     * @desc 结束
     */
    public function end(){
        $contents = ob_get_contents();
        ob_end_clean();
        $this->getResponse()->end($contents);
    }
}