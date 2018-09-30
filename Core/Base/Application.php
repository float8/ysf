<?php
/**
 * @desc:应用初始化
 * @author: wanghongfeng
 * @date: 2017/10/19
 * @time: 下午3:36
 */
namespace Core\Base;

defined('APP_PATH') or die('"APP_PATH" constant not define');
define('PROJECT_NAME', basename(APP_PATH));//项目名称
define('__ENVIRON__', \Yaf\Application::app()->environ());//定义环境变量常量

use Core\Utils\Tools\Fun;
use Yaf\Request\Simple;

/**
 * Class Application
 * @package Core\Base
 */
class Application
{
    /**
     * @desc 运行web模式
     * @used-by cgi
     * @used-by cli
     * @param \Yaf\Application $app
     */
    public static function run(\Yaf\Application $app)
    {
        call_user_func([__NAMESPACE__ . '\Application', PHP_SAPI === 'cli' ? 'cli' : 'cgi'], $app);
    }

    /**
     * @desc 运行cgi模式
     * @param \Yaf\Application $app
     */
    public static function cgi(\Yaf\Application $app)
    {
        Log::startTime();//记录开始时间
        self::debug();//开启关闭debug
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
    public static function cli(\Yaf\Application $app)
    {
        self::debug();//开启关闭debug
        Log::recordLastError();//记录错误日志
        $app->bootstrap();//call bootstrap methods defined in Bootstrap.php
        $app->getDispatcher()->dispatch(new Simple());
    }

    /**
     * @desc 开启/关闭 session
     */
    public static function session()
    {
        if (Config::app('yaf.app.session', false)) {
            session_start();
        }
    }

    /**
     * @desc 开启/关闭 debug
     */
    public static function debug()
    {
        $errors = Config::app('yaf.app.errors');
        ini_set('display_errors', Fun::get($errors, 'debug', false));
        error_reporting(Fun::get($errors, 'level', E_ALL));
    }
}