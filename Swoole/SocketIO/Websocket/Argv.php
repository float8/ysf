<?php
/**
 * @desc:
 * @author: wanghongfeng
 * @date: 2017/11/10
 * @time: 下午3:54
 */

namespace Swoole\SocketIO\Websocket;


trait Argv
{
    /**
     * @desc 命令
     * @var string
     */
    private $command = '';

    /**
     * @desc 开启守护进程
     * @var int
     */
    private $daemonize = 0;

    /**
     * 解析参数
     */
    private function parserArgv(){
        $this->help();//帮助
        $this->command = $_SERVER['argv'][1];//设置命令
        if(isset($_SERVER['argv'][2]) && $_SERVER['argv'][2] == '-d'){//开启守护进程
            $this->daemonize = 1;
        }
    }

    /**
     * @desc 帮助选项
     */
    private function itemOutput(){
        if(
            isset($_SERVER['argv'][1]) &&
            $_SERVER['argv'][1] == '--help' &&
            isset($_SERVER['argv'][2]) &&
            isset($this->commands[$_SERVER['argv'][2]])
        )
        {
            echo "------------------------------------------------------------\n";
            echo "{$_SERVER['argv'][2]}: {$this->commands[$_SERVER['argv'][2]]} \n";
            echo "------------------------------------------------------------\n";
            die;
        }

    }

    /**
     * @desc 输出
     */
    private function output(){
        $commands = implode(' / ', array_keys($this->commands));
        echo "------------------------------------------------------------\n";
        echo " 服务命令 {$commands} \n";
        echo "------------------------------------------------------------\n";
        echo " 守护进程 -d \n";
        echo "------------------------------------------------------------\n";
        die;
    }

    /**
     * @desc 输出帮助
     */
    private function help(){
        $this->itemOutput();//选项输出
        if(
            !isset($_SERVER['argv'][1]) ||
            !isset($this->commands[$_SERVER['argv'][1]]) ||
            ($_SERVER['argv'][1] == '--help' && !isset($_SERVER['argv'][2])) ||
            ($_SERVER['argv'][1] == '--help' && isset($_SERVER['argv'][2]) &&  !isset($this->commands[$_SERVER['argv'][2]]) ||
            ($_SERVER['argv'][1] != '--help' && isset($_SERVER['argv'][2]) && $_SERVER['argv'][2] != '-d')
            )
        ) {
            $this->output();
        }
    }

}