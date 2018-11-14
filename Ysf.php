<?php
/**
 * @desc: 框架入口文件
 * @author: wanghongfeng
 * @date: 2018/10/24
 * @time: 下午10:20
 */

defined('APP_PATH') or die('"APP_PATH" constant not define');

define('PROJECT_NAME', basename(APP_PATH));//项目名称
define('YSF_PATH' , defined(__ROOT_DIR__) ? __ROOT_DIR__ : dirname(__FILE__));

use Core\Base\Config;
use Core\Base\Hook;
use Core\Base\Log;
use Core\Utils\Tools\Fun;
use RealTime\Base\Command;
use RealTime\Base\Config as RConfig;
use RealTime\Protocol\Protocol;

class Ysf
{
    /**
     * @desc 启动
     * @param $config
     */
    public static function swoole($config)
    {
        define('APP_EXT', 'swoole');//项目使用的扩展
        define('__ENVIRON__', get_cfg_var('swoole.environ') ?: 'master');//定义环境变量常量

        RConfig::config($config);//加载系统配置
        self::debug();//debug
        Hook::init();//初始化钩子
        Command::execute();//执行命令
        new Protocol();//根据协议前启动程序
    }

    /**
     * @desc 运行web模式
     * @used-by cgi
     * @used-by cli
     * @param \Yaf\Application $app
     */
    public static function yaf(\Yaf\Application $app)
    {
        define('APP_EXT', 'yaf');//项目使用的扩展
        define('__ENVIRON__', \Yaf\Application::app()->environ());//定义环境变量常量

        self::debug();//开启关闭debug
        Hook::init();//初始化钩子

        call_user_func([__NAMESPACE__ . '\Ysf', PHP_SAPI === 'cli' ? 'cli' : 'cgi'], $app);
    }

    /**
     * @desc 运行cgi模式
     * @param \Yaf\Application $app
     */
    private static function cgi(\Yaf\Application $app)
    {
        Log::startTime();//记录开始时间
        self::session();//开启关闭session
        Log::recordLastError();//记录错误日志
        Log::request();//记录请求日志
        $app->bootstrap();//call bootstrap methods defined in Bootstrap.php
        $app->run();//运行
        Log::memory();//记录内存使用情况
        Log::runTime();//记录运行时间
    }

    /**
     *
     * @desc 运行cli模式(php index.php request_uri="/index/index/id/1")
     * @param \Yaf\Application $app
     */
    private static function cli(\Yaf\Application $app)
    {
        Log::recordLastError();//记录错误日志
        $app->bootstrap();//call bootstrap methods defined in Bootstrap.php
        $app->getDispatcher()->dispatch(new \Yaf\Request\Simple());
    }

    /**
     * @desc 开启/关闭 session
     */
    private static function session()
    {
        if (Config::app('app.session', false)) {
            session_start();
        }
    }

    /**
     * @desc 开启/关闭 debug
     */
    private static function debug()
    {
        $errors = Config::app('app.errors');
        ini_set('display_errors', Fun::get($errors, 'debug', false));
        error_reporting(Fun::get($errors, 'level', E_ALL));
    }

}