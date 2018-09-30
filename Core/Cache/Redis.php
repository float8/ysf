<?php
namespace Core\Cache;

use Core\Base\Config;
use Core\Base\Instances;
use Core\Utils\Tools\Fun;
use Exception;

/**
 * Class Redis
 * @package Core\Cache
 */
abstract class Redis
{
    /**
     * @desc 组
     * @var string
     */
    private $_group = null;

    /**
     * @desc 数据库主从
     * @var string
     */
    private $_MasterSlave = 'master';

    /**
     * @desc 断开重连
     * @var bool
     */
    private $_reconnect = false;

    /**
     * @desc 失败次数
     * @var int
     */
    private $_failNum = 0;

    /**
     * @desc 从服务器命令,命令请全部使用小写 ['get','Mget']
     * @var array
     */
    public $_slaveCmds = [];

    /**
     * @desc 设置分组
     * @param $name
     */
    public function setGroup($name = 'db')
    {
        $this->_group = $name;
    }

    /**
     * @desc 数据库联接
     * @return \Redis
     */
    public function db()
    {
        //存在主从的获取方式
        $db = Instances::get('redis', [$this->_group, $this->_MasterSlave]);
        //不存在主从获取方式
        $db = empty($db) ? Instances::get('redis', [$this->_group]) : $db;

        //获取连接
        if( ($db && !$this->_reconnect) ||  ($db && $this->_reconnect && $this->ping($db)) ){
            return $db;
        }

        return $this->dbconnect();//联接数据库
    }

    /**
     * @desc 设置为自动断开重连
     * @param bool $reconnect
     * @return $this
     */
    public function setAutoReconnect($reconnect = true){
        $this->_reconnect = $reconnect;
        return $this;
    }

    /**
     * @desc 连接断开重连
     * @param \Redis $db
     * @return bool
     * @throws Exception
     */
    private function ping(\Redis $db)
    {
        //如果三次未连接成功直接抛异常
        if($this->_failNum > 3){
            throw new Exception('Reconnection redis failure');
        }
        //如果重连 sleep
        if($this->_failNum > 0){
            sleep(1);
        }
        try {
            @$db->ping();
        } catch (\RedisException $e) {
            $this->_failNum++;
            return false;
        }
        $this->_failNum = 0;
        return true;
    }

    /**
     * @desc 联接
     * @return \Redis
     * @throws Exception
     */
    public function dbconnect()
    {
        //获取配置文件信息
        $config = Config::env('redis.'.$this->_group);
        if(empty($config)) { //不存在配置
            throw new Exception("There is no \"{$this->_group}\" group");
        }

        //是否存在主从配置
        $isMasterSlave = false;
        if (isset($config[$this->_MasterSlave])) {//存在主从配置
            $config = $config[$this->_MasterSlave];
            $isMasterSlave = true;
        }

        //redis 联接信息
        $hostname = Fun::get($config, 'hostname');//主机地址
        $port = Fun::get($config, 'port', 6379);//端口

        //连接
        $redis = new \Redis();

        //长连接设置
        $pconnect = Fun::get($config, 'pconnect');
        $cli_pconnect = Fun::get($config, 'cli_pconnect');
        $cgi_pconnect = Fun::get($config, 'cgi_pconnect');
        if($pconnect || (PHP_SAPI === 'cli' && $cli_pconnect) || (PHP_SAPI !== 'cli' && $cgi_pconnect) ) {
            ini_set('default_socket_timeout', -1);
            $redis->pconnect($hostname, $port);
        } else {
            $redis->connect($hostname, $port);
        }

        //权限
        $auth = Fun::get($config, 'auth');
        if (!empty($auth)) {//授权
            $redis->auth($auth);
        }

        //选择数据库
        $dbindex = Fun::get($config, 'dbindex');
        if (is_numeric($dbindex)) {//数据库
            $redis->select($dbindex);
        }

        //保存数据库联接
        $isMasterSlave ?
            Instances::set('redis', $redis, [$this->_group, $this->_MasterSlave]) : //有主从配置
            Instances::set('redis', $redis, [$this->_group]);//无主从配置

        return $redis;
    }

    /**
     * @return \Redis
     */
    public function master()
    {
        if (empty($this->_MasterSlave)) {
            $this->_MasterSlave = 'master';
        }
        return $this->db();
    }

    /**
     * @return \Redis
     */
    public function slave()
    {
        if (empty($this->_MasterSlave)) {
            $this->_MasterSlave = 'slave';
        }
        return $this->db();
    }

    /**
     * @return $this|self|\Redis
     */
    public static function getInstance()
    {
        static $obj = null;
        return empty($obj) ? ($obj = new static()) : $obj;
    }

    /**
     * @param $name
     * @param $args
     * @return \Redis
     */
    public function __call($name, $args)
    {
        static $_slaveCmds = null;
        //主
        if (empty($this->_slaveCmds)){
            return call_user_func_array([$this->master(), $name], $args);
        }

        //从
        $_name = strtolower($name);
        $_slaveCmds === null ? array_reverse($this->_slaveCmds) : [];
        if(isset($_slaveCmds[$_name])){
            return call_user_func_array([$this->slave(), $name], $args);
        }
        //主
        return call_user_func_array([$this->master(), $name], $args);
    }

    public function __clone()
    {
    }
}