<?php
/**
 * @desc: web服务
 * @author: wanghongfeng
 * @date: 2017/11/1
 * @time: 上午11:54
 */
namespace Swoole\SocketIO\Websocket\Http;

use Core\Utils\Tools\Fun;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\SocketIO\Handshake;

class Server
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
     * @see \Swoole\WebSocket\Server
     * @var null
     */
    private $server = null;

    /**
     * @var array
     */
    private $pathInfo = null;

    /**
     * @desc 目录层级
     * @var int
     */
    private $dirCnt = 0;

    /**
     * @desc 默认控制器
     * @var string
     */
    private $defaultController = 'index';

    /**
     * @desc 默认操作
     * @var string
     */
    private $defaultAction = 'index';

    /**
     * @var string
     */
    private $controller = '';

    /**
     * @var string
     */
    private $action = '';

    /**
     * @var \Swoole\WebSocket\Server
     */
    private $swoole = null;

    public function __construct(Request $request, Response $response, \Swoole\SocketIO\Websocket\Server $server, \Swoole\WebSocket\Server $swoole)
    {
        $this->request = $request;
        $this->response = $response;
        $this->server = $server;
        $this->swoole = $swoole;
        $this->setPathInfo()
            ->setController()
            ->setAction();
    }

    /**
     * @desc 运行方法
     */
    public function run() {
        try {
            if(!$this->socketio() && !$this->isExistFile()) {
                $this->runAction();//运行action
            }
        } catch (\Throwable $e) {
            Fun::syslog(LOG_ERR, $e->getMessage(), $e->getCode(), $e->getFile());
            $this->response->status($e->getCode());
            $this->response->end($e->getMessage());
        }
    }

    /**
     * 处理特殊请求-握手
     * @return bool
     */
    private function socketio(){
        if(strpos($this->request->server['path_info'], 'socket.io') === false){
            return false;
        }
        Handshake::polling($this->request, $this->response);
        return true;
    }

    /**
     * @desc 运行action
     */
    private function runAction(){
        //判断是否存在action
        $actionFile = APP_PATH.'/application/actions/'.ucfirst($this->controller).'/'.ucfirst($this->action).'.php';
        if(!file_exists($actionFile)){
            $this->response->end("Not found action file:{$actionFile}");
            return ;
        }

        include_once $actionFile;
        $actionName = $this->action.'Action';
        if(!class_exists($actionName)) {
            $this->response->end("Not found action:{$actionName}");
            return ;
        }

        $action = new $actionName();
        call_user_func([$action,'setRequest'], $this->request);
        call_user_func([$action,'setResponse'], $this->response);
        call_user_func([$action,'setServer'], $this->server);
        call_user_func([$action,'setSwoole'], $this->swoole);
        call_user_func([$action,'execute']);
        call_user_func([$action,'end']);
        $action = null;
    }

    /**
     * @desc 设置控制器
     * @return $this
     */
    private function setController() {
        if(empty($this->pathInfo)){//pathInfo不存在时设置为默认控制器
            $this->controller = $this->defaultController;
            return $this;
        }
        $controller = [];
        foreach ($this->pathInfo as $k=>$v){
            if( $this->dirCnt > 1 && $k+1 == $this->dirCnt ){
                break;
            }
            $controller[] = $this->parserUrl($v);
        }
        $this->controller = implode('/', $controller);
        return $this;
    }

    /**
     * @return $this
     */
    private function setAction() {
        if( $this->dirCnt > 1 ){
            $this->action = end($this->pathInfo);
        } else {
            $this->action = $this->defaultAction;
        }
        $this->action = $this->parserUrl( $this->action );
        return $this;
    }

    /**
     * @param $str
     * @return string
     */
    private function parserUrl($str) {
        if(!strpos($str, '.') && !strpos($str, '_')) {
            return ucfirst(strtolower($str));
        }
        $str = str_replace(['.'], '_', $str);
        $str = explode('_', $str);
        $_str = '';
        foreach ($str as $v){
            $_str .= ucfirst(strtolower($v));
        }
        return $_str;
    }

    /**
     * @desc 设置path_info
     * @return $this
     */
    private function setPathInfo() {
        $path_info = $this->request->server['path_info'];
        $path_info = preg_replace('/\s(?=\/)/','/',$path_info);
        $path_info = trim($path_info, '/');
        $this->pathInfo = explode('/', $path_info);
        $this->dirCnt = count($this->pathInfo);
        return $this;
    }

    /**
     * @desc 是否存在文件
     * @return bool
     */
    private function isExistFile() {
        if(file_exists(APP_PATH.$this->request->server['path_info'])){
            return true;
        }
        return false;
    }

}