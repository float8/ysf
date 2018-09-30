<?php
/**
 * @desc:
 * @author: wanghongfeng
 * @date: 2017/10/30
 * @time: 下午5:51
 */

namespace Swoole\SocketIO\Websocket;

use Core\Utils\Tools\Fun;
use Swoole\SocketIO\Websocket\Event\Master;
use Swoole\SocketIO\Websocket\Event\Worker;

class Server
{
    //方法
    use Method;
    //命令行参数
    use Argv;
    //命令
    use Command;
    //work event
    use Worker;
    //Master event
    use Master;

    /**
     * @see \Swoole\WebSocket\Server
     * @var Server
     */
    public $server = null;

    /**
     * @desc swoole 事件
     * @var array
     */
    private $swooleEvent = [
        //Master进程内的回调函数
        'Start',
        'Shutdown',
        'MasterConnect',
        'MasterClose',
        'Timer',
        //Worker进程内事件
        'WorkerStart',
        'WorkerStop',
        'WorkerError',
        'Timer',
        'Finish',
        'Packet',
        'BufferFull',
        'BufferEmpty',
        'PipeMessage',
        //Task进程内的回调函数
        'Task',
        //Manager进程内的回调函数
        'ManagerStart',
        'ManagerStop'
    ];

    /**
     * @desc 自定义事件
     * @var array
     */
    private $event = [
        'ping'=>1,
        'upgrade'=>1
    ];

    /**
     * Server constructor.
     * @param $host
     * @param $port
     */
    public function __construct($host, $port)
    {
        $this->parserArgv();//解析参数
        $this->runCommand();//命令
        $this->server = new \Swoole\WebSocket\Server($host, $port);
    }

    /**
     * @desc 是否存在事件函数
     * @param $string
     * @return bool
     */
    public function isUserEvent($string)
    {
        //解析event名称
        $string = explode(' ', $string);
        $eventname = '';
        foreach ($string as $v){
            $eventname .= empty($v) ? '' : ucfirst($v);
        }
        //检查event文件是否存在
        $eventFile = APP_PATH."/application/events/{$eventname}.php";
        if(!file_exists($eventFile)){//文件不存在
            return false;
        }
        if(!class_exists($eventname)) {//类不存在时执行引入
            include_once $eventFile;//引入文件
        }
        $eventname = $eventname.'Event';//拼合类名
        if(!class_exists($eventname)){//判断类是否存在
            return false;
        }
        $event = new $eventname();//实例化类
        return $event;
    }

    /**
     * @desc 设置swoole_server运行时的各项参数
     * @return $this
     */
    public function set(){
        $set = Fun::config('webim.env.set');
        if($this->daemonize) {
            $set['daemonize'] = 1;
        }
        if(!empty($set)) {//设置swoole_server运行时的各项参数
            $this->server->set($set);
        }
        return $this;
    }

    /**
     * @desc 动态执行swoole事件
     * @return $this
     */
    public function onSwooleEvent() {
        foreach ($this->swooleEvent as $v) {
            $method = 'on'.ucfirst($v);
            if(method_exists($this, $method)){
                call_user_func([$this, $method]);
            }
        }
        return $this;
    }

    /**
     * @desc 运行用户事件
     * @param $eventname
     * @param $swoole
     * @param $fd
     * @param $data
     */
    public function runUserEvent($eventname, $swoole, $fd = null, $data = null){
        $event = $this->isUserEvent($eventname);
        if( !$event){
            return ;
        }
        //执行用户函数
        $this->onUserEvent($event, [
            'swoole'=>$swoole,
            'fd'=>$fd,
            'data'=>$data
        ]);
        //销毁对象
        $event = null;
    }

    /**
     * @desc 用户事件
     * @param $event
     * @param $swoole
     * @param null $fd
     * @param null $data
     */
    public function onUserEvent($event, $data, $excArgs = [] ){
        call_user_func([$event, 'setServer'], $this);

        //设置swoole对象
        $swoole = Fun::getArrayValue($data, 'swoole');
        $swoole = empty($swoole) ? $this->server : $swoole;
        call_user_func([$event, 'setSwoole'], $swoole);

        //设置连接编号
        $fd = Fun::getArrayValue($data, 'fd');
        if(!empty($fd)) {
            call_user_func([$event, 'setFd'], $fd);
        }

        //设置数据
        $data = Fun::getArrayValue($data, 'data');
        if(!empty($data)){
            call_user_func([$event, 'setData'], $data);
        }

        //执行方法
        call_user_func_array([$event, 'execute'], $excArgs);
        $event = null;
    }

    /**
     * @see \Swoole\WebSocket\Server::start()
     */
    public function run() {
        //初始化事件
        $this->set()//设置配置参数
            ->onSwooleEvent()//swoole事件
            ->onOpen()//成功链接
            ->onClose()//关闭链接
            ->onRequest()//http请求
            ->onMessage();//消息事件

        //设置进程名称
        cli_set_process_title(Fun::config('webim.env.server.process_title'));
        //启动服务
        $this->server->start();
    }

    /**
     * @param $name
     * @param $arguments
     */
    public function __call($name, $arguments)
    {
        if( strpos($name, 'on') ){//执行Swoole Server事件
            call_user_func_array([$this->server, $name], $arguments);
        }
    }

}