<?php
/**
 * @desc:
 * @author: wanghongfeng
 * @date: 2018/11/8
 * @time: 下午8:33
 */

namespace RealTime\Base;


use Core\Base\Log;
use Core\Utils\Tools\Fun;
use Error;
use Exception;
use RealTime\Engine\SocketIO\Emitter;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\WebSocket\Frame;
use Swoole\WebSocket\Server;

class Route
{
    /**
     * @desc action 后缀
     * @var string
     */
    private $actionSuffix = 'Action';

    /**
     * @desc 500 错误页面
     * @var string
     */
    private $page_500 = YSF_PATH . '/Core/Error/500';

    /**
     * @desc 错误页面
     * @var string
     */
    private $page_error = YSF_PATH . '/Core/Error/error';

    /**
     * @desc 模块的打开连接事件
     * @param $emitter
     * @param $route
     */
    private function _connect($emitter, $route)
    {
        if($connect = Loader::module('Connect', $route['module']) && $route['module'] != 'Index'){
            $connect->emitter = $emitter;
            call_user_func_array([$connect, 'execute'], array_slice(func_get_args(), 2));
        }
    }

    /**
     * @desc event route
     * @param $emitter
     * @param $route
     */
    private function _event($emitter, $route)
    {
        //没有controller
        $controller = Loader::controller('Event', $route['controller'], 'Controller', $route['module']);
        $controller or $this->throw('"'.$route['controller'].'Controller" controller does not exist', 404);
        $action = $route['action'];
        $_route = array_slice($route, 0, 3);//设置路由
        $controller->route = $_route;

        //controller action 为同一文件时
        if(empty($controller->actions) || !isset($controller->actions[$action])) {
            $controller->emitter = $emitter;
            $method = $route['action'].$this->actionSuffix;
            method_exists($controller, $method) and call_user_func_array([$controller, $method],
                array_merge([$route['id']], $route['data'], array_slice(func_get_args(), 2)));
            goto _return;
        }

        //action 分离时
        $action = Loader::action($controller->actions[$action], $action, 'Action', $route['module']);
        $action or $this->throw('"'.$action.'Action" action does not exist', 404);
        $action->emitter = $emitter;
        $action->route = $_route;
        $action->controller = $controller;
        call_user_func_array([$action, 'execute'],
            array_merge([$route['id']], $route['data'], array_slice(func_get_args(), 2)));
        _return:
    }

    /**
     * @desc socket事件
     * @param $event
     * @param $emitter
     * @param $route
     * @param Server $server
     * @param int $fd
     * @param int $reactor_id
     * @param string $data
     */
    public function onReceive($event, $emitter, $route, Server $server, int $fd, int $reactor_id, string $data)
    {
        call_user_func([$this, '_'.$event], $emitter, $route, $server, $fd, $reactor_id, $data);
    }

    /**
     * @used-by _connect
     * @used-by _event
     * @desc websocket 事件
     * @param $event
     * @param Emitter $emitter
     * @param $route
     * @param Server $server
     * @param Frame $frame
     */
    public function onMessage($event, $emitter, $route, Server $server, Frame $frame)
    {
        try {
            call_user_func([$this, '_'.$event], $emitter, $route, $server, $frame);
        } catch (\Exception $e) {
            $emitter->emitError($e->getMessage());
        }
    }

    /**
     * @desc 监听http请求
     * @param $emitter
     * @param $route
     * @param Request $request
     * @param Response $response
     */
    public function onRequest($emitter, $route, Request $request, Response $response)
    {
        $this->write($response, function () use($route, $request, $emitter, $response){
            $this->web($emitter, $route, $request, $response);
        });
    }

    /**
     * @desc web
     * @param $emitter
     * @param $route
     * @param Request $request
     * @param Response $response
     */
    private function web($emitter, $route, Request $request,Response $response)
    {
        $request->get = $request->get ?  array_merge($request->get, $route['query']) : $route['query'];
        $controller = Loader::controller('Web', $route['controller'], 'Controller', $route['module']);
        $controller or $this->throw('"'.$route['controller'].'Controller" controller does not exist', 404);
        $action = $route['action'];
        $_route = array_slice($route, 0, 3);//设置路由
        $controller->route = $_route;

        //controller action 为同一文件时
        if(empty($controller->actions) || !isset($controller->actions[$action])) {
            $controller->emitter = $emitter;
            $method = $route['action'].$this->actionSuffix;
            method_exists($controller, $method) or $this->throw('"'.$action.'Action" action does not exist', 404);
            call_user_func_array([$controller, $method], array_slice(func_get_args(), 2));
            goto _return;
        }

        //action 分离时
        $action = Loader::action($controller->actions[$action], $action, 'Action', $route['module']);
        $action or $this->throw('"'.$action.'Action" action does not exist', 404);
        $action->emitter = $emitter;
        $action->route = $_route;
        $action->controller = $controller;
        call_user_func_array([$action, 'execute'], array_slice(func_get_args(), 2));
        _return:
    }

    /**
     * @desc 写数据
     * @param Response $response
     * @param $callable
     */
    private function write(Response $response, $callable)
    {
        ob_start();
        try {
            $callable();
        } catch (Exception $e) {
            $this->errorPage($e, $response);
        } catch (Error $e) {
            $this->errorPage($e, $response);
        }
        $content = ob_get_contents();
        ob_end_clean();
        $response->end($content);
    }

    /**
     * @desc 500 error
     * @param Exception $e
     * @param Response $response
     */
    private function errorPage($e, Response $response)
    {
        Log::write(LOG_ERR, $e);
        //获取配置信息
        $errors = \Core\Base\Config::app('app.errors');
        if(!Fun::get($errors, 'debug', true)){
            return ;
        }
        $data = [
            'code' => $e->getCode(),
            'message'=> $e->getMessage(),
            'line' => $e->getLine(),
            'file'=> $e->getFile(),
            'trace'=> $e->getTraceAsString()
        ];
        extract($data);
        $response->status($data['code'] ?: 500);
        //app 404 page
        if($data['code'] == 404 && isset($errors[404])){
            $pageFile = APP_PATH . $errors['404'];
            goto _include;
        }
        //app 500 page
        if(isset($errors[500])){
            $pageFile = APP_PATH . $errors['500'];
            goto _include;
        }
        // Exception page
        if($e instanceof  Exception) {
            $pageFile = $this->page_500;
            goto _include;
        }
        //Error page
        if($e instanceof  Error) {
            $pageFile = $this->page_error;
            goto _include;
        }
        _include :
        include $pageFile.'.phtml';
    }

    /**
     * @desc 抛异常
     * @param $message
     * @param int $code
     * @throws Exception
     */
    public function throw($message, $code = 0)
    {
        throw new Exception($message, $code);
    }
}