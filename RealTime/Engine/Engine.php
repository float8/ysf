<?php
/**
 * @desc:
 * @author: wanghongfeng
 * @date: 2018/10/25
 * @time: 上午10:46
 */

namespace RealTime\Engine;

use Core\Base\Config;
use RealTime\Base\Route;

class Engine
{
    /**
     * @desc 引擎
     * @var array
     */
    private static $engines = ['socketio'=>'\RealTime\Engine\SocketIO\Engine'];

    /**
     * @var \RealTime\Engine\SocketIO\Engine
     */
    public static $engine;

    /**
     * @var \Swoole\Websocket\Server
     */
    private static $server;

    /**
     * @var array
     */
    private static $modules;

    /**
     * @var Route
     */
    private static $route;

    /**
     * @desc 初始化
     * @param $server
     */
    public static function init($server)
    {
        $engine = Config::app('app.server.engine', 'socketio');
        if(!isset(self::$engines[$engine])){
            die('The engine does not exist');
        }
        //swoole服务
        self::$server = $server;
        //模块
        self::$modules = Config::app('swoole.modules', 'Index');
        self::$modules = explode(',' , self::$modules);
        self::$modules = array_flip(self::$modules);
        self::$modules['Index'] = 0;
        //实例化引擎
        self::$engine = new self::$engines[$engine]($server, self::$modules);
        //路由
        self::$route = new Route();
    }

    /**
     * @desc 执行事件
     * @param $event
     * @param $params
     */
    public static function on($event, $params)
    {
        $events = ['open'=>1, 'connect'=>1];//回调函数
        !isset($events[$event]) and $params['callable'] = [self::$route,  'on'.ucfirst($event)];
        call_user_func_array([self::$engine, 'on'.ucfirst($event)], $params);//执行事件
    }


}