<?php
/**
 * @desc:
 * @author: wanghongfeng
 * @date: 2017/11/10
 * @time: 下午4:54
 */

namespace Swoole\SocketIO\Websocket;


use Core\Base\Config;

trait Command
{
    private $commands = ['--help'=>'帮助','start'=>'启动', 'reload'=>'平滑重启', 'stop'=>'停止', 'restart'=>'重启','status'=>'状态'];

    /**
     * @uses start
     * @uses reload
     * @uses stop
     * @uses restart
     * @uses status
     * @desc 执行命令
     * @return $this
     */
    private function runCommand(){
        call_user_func([$this, $this->command]);
        return $this;
    }

    /**
     * @desc 获取pid
     * @return bool|string
     */
    private function getPid(){
        $pid_file = Config::env('webim.set.pid_file');
        if(!file_exists($pid_file)){
            return false;
        }
        return file_get_contents($pid_file);
    }
    /**
     * 开始执行
     */
    private function start(){
        if($this->isRun()){
            die("服务运行中...\n");
        }
    }

    /**
     * @desc 平滑重启
     */
    private function reload() {
        if(!$this->isRun()){
            die("服务未运行\n");
        }
        $pid = $this->getPid();
        exec("kill -USR1 {$pid}");
        die("平滑重启完成\n");
    }

    /**
     * @desc 停止
     */
    private function stop($reset = false){
        if(!$this->isRun()){
            die("服务未运行\n");
        }
        $pid = $this->getPid();
        exec("kill {$pid}");
        if(!$reset){
            die("服务已关闭\n");
        }
    }

    /**
     * @desc 平滑重启
     */
    private function restart(){
        $this->stop(true);
        sleep(2);//等待服务完全关闭
        $this->daemonize = 1;//开启守护进程
    }

    /**
     * @desc 是否运行
     * @return bool
     */
    private function isRun(){
        $pid = $this->getPid();
        if(!$pid){
            return false;
        }
        return \Swoole\process::kill($pid, 0);
    }

    /**
     * @desc 状态
     */
    private function status() {
        if(!$this->isRun()){
            die("服务未运行\n");
        }

        echo "------------------------------------------------------------\n";
        echo " server: ", Config::env('webim.server.host'), ":", Config::env('webim.server.port'), "\n";
        echo "------------------------------------------------------------\n";
        echo " server name: ", Config::env('webim.server.process_title'), "\n";
        echo "------------------------------------------------------------\n";
        echo " work进程数: ", Config::env('webim.set.worker_num'), "\n";
        echo "------------------------------------------------------------\n";
        echo " reactor线程数: ", Config::env('webim.set.reactor_num'), "\n";
        echo "------------------------------------------------------------\n";
        die;
    }

}