<?php
/**
 * @desc: 命令
 * @author: wanghongfeng
 * @date: 2017/11/10
 * @time: 下午4:54
 */

namespace RealTime\Base;

use Core\Base\Config;
use Swoole\process;

class Command
{
    /**
     * @desc 命令
     * @var string
     */
    private static $command = '';

    /**
     * @desc 所有命令
     * @var array
     */
    private static $commands = ['help', 'start', 'reload', 'stop', 'restart', 'status'];

    /**
     * @desc 守护进程
     * @var int 0:关闭;1开启
     */
    private static $daemonize = 0;

    /**
     * @desc 语言
     * @var array
     */
    private static $langs = [
        'running'=>"running...\n",
        'unrun'=>"not running\n",
        'starting'=>'',
    ];

    /**
     * @uses cmdHelp
     * @uses cmdStart
     * @uses cmdReload
     * @uses cmdStop
     * @uses cmdRestart
     * @uses cmdStatus
     * @desc 执行命令
     */
    public static function execute()
    {
        self::$command = empty($_SERVER['argv'][1]) ? 'help' : $_SERVER['argv'][1];//设置命令
        self::$daemonize = isset($_SERVER['argv'][2]) ? ($_SERVER['argv'][2] == '-d' ? 1 : 0) : 0;//守护进程
        self::$command = in_array(self::$command, self::$commands) ? self::$command : 'help';
        call_user_func([__CLASS__, 'cmd' . self::$command]);//执行命令
    }


    /**
     * @desc 帮助
     */
    private static function cmdHelp()
    {
        $commands = implode(' / ', self::$commands);
        echo "------------------------------------------------------------\n";
        echo " command {$commands} \n";
        echo "------------------------------------------------------------\n";
        echo " daemon -d \n";
        echo "------------------------------------------------------------\n";
        die;
    }

    /**
     * 开始执行
     */
    private static function cmdStart()
    {
        !self::isRun() or die(self::$langs['running']);
    }

    /**
     * @desc 平滑重启
     */
    private static function cmdReload()
    {
        self::isRun() or die(self::$langs['unrun']);
        exec("kill -USR1 " . self::getPid());
    }

    /**
     * @desc 停止
     */
    private static function cmdStop()
    {
        self::isRun() or die(self::$langs['unrun']);
        exec("kill " . self::getPid());
    }

    /**
     * @desc 平滑重启
     */
    private static function cmdRestart()
    {
        self::$daemonize = 1;//开启守护进程
        self::cmdStop();//停止服务
        sleep(2);//等待服务完全关闭
    }

    /**
     * @desc 状态
     */
    private function cmdStatus()
    {
        self::isRun() or die(self::$langs['unrun']);

        echo "------------------------------------------------------------\n";
        echo " server: ", Config::env('webim.server.host'), ":", Config::env('webim.server.port'), "\n";
        echo "------------------------------------------------------------\n";
        echo " server name: ", Config::env('webim.server.process_title'), "\n";
        echo "------------------------------------------------------------\n";
        echo " work progress: ", Config::env('webim.set.worker_num'), "\n";
        echo "------------------------------------------------------------\n";
        echo " reactor thread: ", Config::env('webim.set.reactor_num'), "\n";
        echo "------------------------------------------------------------\n";
        die;
    }

    /**
     * @desc 获取后台运行
     * @return int
     */
    public static function getDaemonize()
    {
        return self::$daemonize;
    }

    /**
     * @desc 获取pid
     * @return bool|string
     */
    private static function getPid()
    {
        $pid_file = Config::env('webim.set.pid_file');
        return file_exists($pid_file) ? file_get_contents($pid_file) : false;
    }

    /**
     * @desc 是否运行
     * @return bool
     */
    private static function isRun()
    {
        return empty($pid) ? false : process::kill(self::getPid(), 0);
    }
}