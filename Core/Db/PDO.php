<?php
namespace Core\Db;

use Core\Base\Config;
use Core\Base\Instances;
use Exception;
use Core\Utils\Tools\Fun;
use PDOException;

abstract class PDO
{
    /**
     * @desc 当前选择的数据库连接组
     * @var string
     */
    private $_group = null;

    /**
     * @desc 数据库主从
     * @var string
     */
    private $_MasterSlave = null;

    /**
     * @desc 驱动选项
     * @var array
     */
    private $_driverOptions = [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION];

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
     * @desc 设置数据库连接组
     * @param string $name
     * @return $this
     */
    public function setGroup($name = 'db')
    {
        $this->_group = $name;
        return $this;
    }

    /**
     * @desc 驱动选项
     * @param array $driver_options
     * @return $this
     */
    public function driverOptions($driver_options = [])
    {
        $this->_driverOptions = $driver_options;
        return $this;
    }

    /**
     * @desc 选择链接数据库 读写分离
     * @return \PDO
     */
    public function db()
    {
        //存在主从的获取方式
        $db = Instances::get('mysql', [$this->_group, $this->_MasterSlave]);
        //不存在主从获取方式
        $db = empty($db) ? Instances::get('mysql', [$this->_group]) : $db;
        //获取连接
        if( ($db && !$this->_reconnect) ||  ($db && $this->_reconnect && $this->ping($db)) ){
            return $db;
        }
        return $this->connect();//联接数据库
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
     * @param \PDO $db
     * @return bool
     */
    private function ping(\PDO $db)
    {
        //如果三次未连接成功直接抛异常
        if($this->_failNum > 3){
            $this->throw('Reconnection mysql failure');
        }
        //如果重连 sleep
        if($this->_failNum > 0){
            sleep(1);
        }
        try {
           @$db->getAttribute(\PDO::ATTR_SERVER_INFO);
        } catch (PDOException $e) {
            $this->_failNum++;
            return false;
        }
        $this->_failNum = 0;
        return true;
    }

    /**
     * @desc pdo联接数据库
     * @return \PDO
     */
    private function connect()
    {
        $config = Config::env('database.'.$this->_group);

        if(empty($config)) { //不存在配置
            $this->throw("There is no \"{$this->_group}\" group");
        }

        //是否存在主从配置
        $isMasterSlave = false;
        if (isset($config[$this->_MasterSlave])) {//存在主从配置
            $config = $config[$this->_MasterSlave];
            $isMasterSlave = true;
        }

        //不存在dsn
        $dsn = Fun::get($config, 'dsn');
        if(empty($dsn)) {
            $this->throw("There is no dsn");
        }

        //如为数组随机获取dsn
        if(is_array($dsn)) {
            $rand_key = array_rand($dsn, 1);
            $dsn = $dsn[$rand_key];
        }

        //不存在用户
        if(!Fun::get($config, 'username')) {
            $this->throw("There is no username");
        }

        //长连接设置
        $persistent = Fun::get($config, 'persistent');
        $cli_persistent = Fun::get($config, 'cli_persistent');
        $cgi_persistent = Fun::get($config, 'cgi_persistent');
        if($persistent || (PHP_SAPI === 'cli' && $cli_persistent) || (PHP_SAPI !== 'cli' && $cgi_persistent) ){
            $this->setDriverOptions(\PDO::ATTR_PERSISTENT, true);
        }

        //pdo 联接数据库
        $pdo = new \PDO($dsn, $config['username'], $config['password'], $this->_driverOptions);

        //保存数据库联接
        $isMasterSlave ?
            Instances::set('mysql', $pdo, [$this->_group, $this->_MasterSlave]) : //有主从配置
            Instances::set('mysql', $pdo, [$this->_group]);//无主从配置

        return $pdo;
    }

    /**
     * @desc 链接主库
     * @return $this
     */
    public function master()
    {
        if (empty($this->_MasterSlave)) {
            $this->_MasterSlave = 'master';
        }
        return $this;
    }

    /**
     * @desc 链接从库
     * @return $this
     */
    public function slave()
    {
        if (empty($this->_MasterSlave)) {
            $this->_MasterSlave = 'slave';
        }
        return $this;
    }

    /**
     * @desc 清除_MasterSlave
     * @return $this
     */
    public function clearMasterSlave()
    {
        $this->_MasterSlave = null;
        return $this;
    }

    /**
     * @desc 抛异常
     * @param $msg
     * @param int $code
     * @throws Exception
     */
    public function throw($msg, $code = 0)
    {
        throw new Exception($msg, $code);
    }

    /**
     * @return array
     */
    public function getDriverOptions(): array
    {
        return $this->_driverOptions;
    }

    /**
     * @param $key
     * @param $value
     */
    public function setDriverOptions($key, $value)
    {
        $this->_driverOptions[$key] = $value;
    }

    /**
     * @return static
     */
    public static function getInstance()
    {
        static $obj = null;
        return empty($obj) ? ($obj = new static()) : $obj;
    }
}