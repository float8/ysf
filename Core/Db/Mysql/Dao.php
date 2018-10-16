<?php
namespace Core\Db\Mysql;

use Core\Utils\Tools\Fun;
use PDO;

abstract class Dao extends \Core\Db\PDO
{
    use Tools;
    use Dynamic;
    use BuildSql;
    /**
     * @desc 表名
     * @var null
     */
    public $_table = null;
    /**
     * @desc 主键
     * @var null
     */
    public $_pk = null;
    /**
     * @desc 字段
     * @var null
     */
    public $_fields = null;
    /**
     * @desc 字段映射
     * @var null
     */
    public $_map = null;
    /**
     * @desc 开启debug
     * @var boolean
     */
    public $_sql_debug = true;
    /**
     * @desc 参数 array(name,type)
     * @var null
     */
    private $_params = null;
    /**
     * @desc 最后的sql
     * @var null
     */
    private $_lastSql = null;
    /**
     * @desc 最后的绑定参数
     * @var array
     */
    private $_lastParams = [];
    /**
     * @see PDO::prepare()
     * @var null
     */
    private $_prepare = null;

    /**
     * @desc 返回sql
     * @var bool
     */
    private $_isReturnSql = false;

    /**
     * @desc 使用模型
     * @var boolean
     */
    protected $_useModelMode = true;

    /**
     * @desc 使用模型
     * @var boolean
     */
    private $_useModelModePrefer = null;

    /**
     * {@inheritDoc}
     * @param $sql
     * @return $this
     * @see PDO::prepare()
     */
    private function prepare($sql)
    {
        if(!$this->_isReturnSql){
            $sql or $this->throw('There is no sql');
            $this->_prepare = $this->db()->prepare($sql);//预处理sql
            $this->clearMasterSlave();//清除主从标识
        }
        return $this;
    }

    /**
     * @desc 执行sql前运行的方法
     * @param $method
     * @param $params
     * @return $this
     */
    private function _before($method, &$params = [])
    {
        if(empty($method) || !$this->_useModelMode) {
            return $this;
        }
        $method = "_{$method}Before";
        if ( method_exists($this->model(), $method) ) {
            $this->model()->$method($params);
            $this->_setSql('data', $params);
        }
        return $this;
    }

    /**
     * @desc 执行sql后运行的方法
     * @param $method
     * @param $result
     * @param $sql
     * @param $params
     * @return mixed
     */
    private function _after($method, $result, $sql, $params)
    {
        if(empty($method) || !$this->_useModelMode) {
            return $result;
        }
        $method = empty($method) ? $method : "_{$method}After";
        if (is_callable($method)) {
            call_user_func($method, $params);
        } else if (method_exists($this->model(), $method)) {
            $this->model()->$method($result, $sql, $params);
        }
        return $result;
    }

    /**
     * @see \PDOStatement::execute()
     * @param $sql
     * @param array $params
     * @param callable|string $method
     * @return mixed
     */
    public function execute($sql, $params = null, $method = null)
    {
        $this->_startTime();//设置开始事件
        $this->callUserFunc($sql);//如果第一个参数为函数时执行
        $this->master();//如果未设置主从，默认使用主库
        $this->prepare($sql);//预处理sql
        $this->bindValues($params);//绑定参数

        //返回sql
        if ($this->_isReturnSql) {
            return $this->getLastSql();
        }

        $result = $this->callPrepareFunc('execute');//执行sql

        //记录日志
        $this->_writeLog();

        //当为字符串时，执行影响数据的执行后方法
        if (is_string($method)) {
            return $this->_after($method, $result, $sql, $params);
        }

        //当为方法时直接执行函数
        if (is_callable($method)) {
            return $this->callUserFunc($method);
        }
        return $result;
    }

    /**
     * @desc 添加
     * @param null $columns
     * @param string|null $table
     * @param string $method
     * @return mixed
     */
    public function insert($columns = null, string $table = null, $method = 'insert')
    {
        $sql = null;
        $params = [];
        $data = is_array($columns) ? $columns : [];//插入的数据
        $this->callUserFunc($columns);//如果第一个参数为函数时执行
        $this->_before($method, $this->_getSql('data',[]));//执行前执行方法
        $this->table($table);//获取表名
        $this->buildInsert();//构建插入语句
        $this->buildData($data);//绑定数据
        $this->buildTable($table);//绑定表名
        $this->build($sql, $params);//构建sql
        return $this->execute($sql, $params, $method);//执行语句
    }

    /**
     * @desc 添加所有
     * @param array $columns
     * @param string $table
     * @return mixed
     */
    public function insertAll($columns = null, string $table = null)
    {
        return $this->insert($columns, $table, 'insertAll');
    }

    /**
     * @see \PDO::lastInsertId;
     * @return string
     */
    public function lastInsertId()
    {
        return $this->master()->db()->lastInsertId();
    }

    /**
     * @desc 修改
     * @param mixed $columns
     * @param string $conditions
     * @param array $params
     * @param string $table
     * @return bool
     */
    public function update($columns = null, string $conditions = null, array $params = null, string $table = null)
    {
        $sql = '';
        $data = is_array($columns) ? $columns : [];
        $this->callUserFunc($columns);//如果第一个参数为函数时执行
        $this->_before('update', $this->_getSql('data',[]));//执行前执行方法
        $this->table($table);//获取表名
        $this->buildUpdate();//创建update语句
        $this->buildTable($table);//获取表名
        $this->buildData($data);//绑定数据
        $this->buildWhere($conditions, $params);//构建条件
        $this->build($sql, $params);//创建sql
        return $this->execute($sql, $params, 'update');//执行语句
    }

    /**
     * @desc 删除
     * @param mixed $conditions
     * @param array $params
     * @param string $table
     * @return mixed
     */
    public function delete($conditions = null, $params = null, string $table = null)
    {
        $sql = '';
        $where = is_callable($conditions) ? null : $conditions;
        $this->callUserFunc($conditions);//如果第一个参数为函数时执行
        $this->_before('delete');//执行前执行方法
        $this->table($table);//获取表名
        $this->buildDelete();//构建删除语句
        $this->buildTable($table);//绑定表名
        $this->buildWhere($where, $params);//构建条件
        $this->build($sql, $params);//创建sql
        return $this->execute($sql, $params, 'delete');//执行语句
    }

    /**
     * @desc 返回一个包含结果集中所有行的数组
     * @param mixed $sql
     * @param array $params
     * @param bool $settModelMode
     * @return mixed
     */
    public function queryAll($sql = null, $params = null, $settModelMode = null)
    {
        $this->callUserFunc($sql);//如果第一个参数为函数时执行
        $this->buildSelect();//构建select查询
        $this->build($sql, $params);//创建查询语句
        $this->slave();//使用从库查询
        return $this->execute($sql, $params, function (Dao $dao) use ($settModelMode) {
            $dao->setModelMode($settModelMode);//设置模型获取模式获取
            return $dao->callPrepareFunc('fetchAll');//获取所有数据
        });
    }

    /**
     * @desc 返回一行数据
     * @param mixed $sql
     * @param array $params
     * @param bool $settModelMode
     * @return mixed
     */
    public function queryRow($sql = null, $params = null, $settModelMode = null)
    {
        $result = null;
        $this->callUserFunc($sql);//如果第一个参数为函数时执行
        $this->buildSelect();//构建select查询
        $this->build($sql, $params);//创建查询语句
        $this->slave();//使用从库查询
        return $this->execute($sql, $params, function (Dao $dao) use ($settModelMode) {
            $dao->setModelMode($settModelMode);//设置模型获取模式获取
            $result = $dao->callPrepareFunc('fetch');//获取数据
            $dao->callPrepareFunc('closeCursor');//获关闭游标
            return $result;
        });
    }

    /**
     * @desc 获取一个标量
     * @param mixed $sql
     * @param array $params
     * @return mixed
     */
    public function queryScalar($sql = null, $params = null)
    {
        $result = null;
        $this->callUserFunc($sql);//如果第一个参数为函数时执行
        $this->buildSelect();//构建select查询
        $this->build($sql, $params);//创建查询语句
        $this->slave();//使用从库查询
        return $this->execute($sql, $params, function (Dao $dao) {
            $result = $dao->callPrepareFunc('fetchColumn', [0]);//获取数据
            $dao->callPrepareFunc('closeCursor');//获关闭游标
            return $result;
        });
    }

    /**
     * @desc 获取一列数据
     * @param mixed $sql
     * @param array $params
     * @return mixed
     */
    public function queryColumn($sql = null, $params = null)
    {
        $result = null;
        $this->callUserFunc($sql);//如果第一个参数为函数时执行
        $this->buildSelect();//构建select查询
        $this->build($sql, $params);//创建查询语句
        $this->slave();//使用从库查询
        return $this->execute($sql, $params, function (Dao $dao) {
            return $dao->callPrepareFunc('fetchAll', [PDO::FETCH_COLUMN]);//获取数据
        });
    }

    /**
     * @desc 搜索
     * @param array $options
     * @return array|mixed
     */
    public function search($options = [])
    {
        $Search = Search::getInstance();
        $Search->setDao($this);//设置模型
        $Search->setOption('table', Fun::get($options, 'table', $this->table()));//设置表名
        $Search->setOption('fields', Fun::get($options, 'fields', $this->_fields));//设置字段
        $Search->setOption('order', Fun::get($options, 'order', "{$this->_pk} desc"));//排序
        $Search->setOption('group', Fun::get($options, 'group'));//分组
        $Search->setOption('where', Fun::get($options, 'where'));//设置条件
        $Search->setOption('size', Fun::get($options, 'size'));//设置查询条数
        $Search->setOption('page', Fun::get($options, 'page'));//设置分页
        $Search->setOption('list_sql', Fun::get($options, 'list_sql', false));//设置查询数据sql
        $Search->setOption('count_sql', Fun::get($options, 'count_sql', false));//设置数量查询sql
        $rows = $Search->getList(); //获取查询结果集
        if (Fun::get($options, 'isGetPage', true)) { //获取总数
            return ['rows' => $rows, 'total' => $Search->getTotal()/*获取总数*/];
        }
        return $rows;
    }

    /**
     * @desc 添加读写锁
     * @param string $type
     * @param string $table
     * @return mixed
     */
    public function lockTable($type = 'read', $table = null)
    {
        $this->table($table);//获取table
        $sql = "lock tables";
        $this->bindSql($sql, "`{$table}`")->bindSql($sql, $type);//绑定sql
        return $this->master()->execute($sql);//执行语句
    }

    /**
     * @desc 释放读锁定
     */
    public function unLockTable()
    {
        return $this->master()->execute("unlock tables");
    }

    /**
     * @desc 返回结果集中的列数
     * @return int
     */
    public function columnCount()
    {
        return $this->callPrepareFunc('columnCount');
    }

    /**
     * @desc 打印一条 SQL 预处理命令
     * @return mixed
     */
    public function debugDumpParams()
    {
        return $this->callPrepareFunc('debugDumpParams');
    }

    /**
     * @see PDO::quote()
     * @param string $string
     * @param int $parameter_type
     * @return string
     */
    public function quote($string, $parameter_type = PDO::PARAM_STR)
    {
        return $this->slave()->db()->quote($string, $parameter_type);
    }

    /**
     * @param $sql
     * @return $this
     */
    protected function setLastSql($sql)
    {
        $this->_lastSql = $sql;
        return $this;
    }

    /**
     * @param $params
     * @return $this
     */
    protected function setLastParams($params)
    {
        $this->_lastParams = $params;
        return $this;
    }

    /**
     * @desc 获取最后一条sql
     * @return string
     */
    public function getLastSql()
    {
        return $this->getPdoSql($this->_lastSql, $this->_lastParams);
    }

    /**
     * @desc 为语句设置为Model的获取模式。
     * @param $value
     * @return $this
     */
    public function setModelMode($value)
    {
        if (!empty($value)) {
            $mode = is_array($value) ? $value : [$value];
        } else if ($this->_useModelMode) {
            $mode = [PDO::FETCH_CLASS, $this->modelName()];
        } else if (!empty($this->_useModelModePrefer)) {
            $mode = is_array($this->_useModelModePrefer) ? $this->_useModelModePrefer : [$this->_useModelModePrefer];
        } else {
            $mode = [PDO::FETCH_NAMED];
        }
        //设置获取模式
        $this->callPrepareFunc('setFetchMode', $mode);
        //清空数据模式
        $this->_useModelModePrefer = null;

        return $this;
    }

    /**
     * @see \PDO::beginTransaction
     * @return boolean
     */
    public function beginTransaction()
    {
        return $this->master()->db()->beginTransaction();
    }

    /**
     * @see \PDO::inTransaction
     * @return boolean
     */
    public function inTransaction()
    {
        return $this->master()->db()->inTransaction();
    }

    /**
     * @see \PDO::rollBack
     * @return boolean
     */
    public function rollBack()
    {
        return $this->master()->db()->rollBack();
    }

    /**
     * @see \PDO::commit
     * @return boolean
     */
    public function commit()
    {
        return $this->master()->db()->commit();
    }

    /**
     * @desc 获取受影响行数
     * @return mixed
     */
    public function rowCount()
    {
        return $this->callPrepareFunc('rowCount');
    }

    /**
     * @desc 获取表名
     * @param string $table
     * @return string
     */
    private function table(&$table = null)
    {
        $table = !empty($table) ? $table : $this->_table;
        $table or $this->throw("There is no \"{$table}\" table name ");
        return $table;
    }

    /**
     * @desc 绑定所有参数
     * @param array $data
     * @param int $index
     * @return $this
     */
    private function bindValues($data = [], &$index = 0)
    {
        $data = empty($this->_params) ? $data : $this->_params;
        if (empty($data)) {
            return $this;
        }
        if (empty($index)) {
            $this->_lastParams = [];//最后绑定的所有参数
        }
        foreach ($data as $k => $v) {
            if (is_array($v)) {
                $this->bindValues($v, $index);
                continue;
            }
            if (is_numeric($k)) {
                $key = ++$index;
            } else {
                $key = (strpos($k, ':') === 0) ? $k : ":{$k}";
            }
            $this->_lastParams[$key] = $v;
            $this->callPrepareFunc('bindValue', [$key, $v, PDO::PARAM_STR]);
        }
        $this->_params = [];
        return $this;
    }

    /**
     * @desc 绑定参数
     * @param $name
     * @param $value
     * @return $this
     */
    public function bindValue($name, $value)
    {
        $this->_params[$name] = $value;
        return $this;
    }

    /**
     * @desc 绑定模型
     * @param mixed $value
     * @return $this
     */
    public function bindModel($value = null)
    {
        $this->_useModelModePrefer = $value;
        return $this;
    }

    /**
     * @desc 实例化模型
     * @return Model_Abstract
     */
    public function model()
    {
        static $model = null;
        if (empty($model)) {
            $model = call_user_func($this->modelName() . '::getInstance');
        }
        return $model;
    }

    /**
     * @desc 获取模型名称
     * @return string
     */
    public function modelName()
    {
        static $modelName = null;
        if (empty($modelName)) {
            $modelName = str_replace('\\Dao\\', '\\Model\\', static::class);
            $modelName = substr($modelName, 0, strlen($modelName) - 8) . 'Model';
        }
        return $modelName;
    }

    /**
     * @desc 执行函数
     * @param $fun
     * @return string
     */
    private function callUserFunc(&$fun)
    {
        if (is_callable($fun)) {
            $fun = call_user_func($fun, $this);
        }
        return $fun;
    }

    /**
     * @param callable $fun
     * @param array $params
     * @return mixed
     */
    private function callPrepareFunc($fun, $params = [])
    {
        $result = null;
        if ($this->_prepare && method_exists($this->_prepare, $fun)) {
            $result = empty($params) ?
                call_user_func(array($this->_prepare, $fun)) :
                call_user_func_array(array($this->_prepare, $fun), $params);
        }
        return $result;
    }

    /**
     * @desc 返回sql
     * @return $this
     */
    public function sql()
    {
        $this->_isReturnSql = true;
        return $this;
    }

    /**
     * 创建动态方法
     * @param string $name
     * @param array $arguments
     * @return mixed
     */
    public function __call(string $name, array $arguments)
    {
        return $this->createMethod($name, $arguments);
    }

    /**
     * @desc 返回sql
     * @return string
     */
    public function __toString()
    {
        return $this->getLastSql();
    }

    /**
     * @param $name
     * @param $value
     */
    public function __set($name, $value)
    {
        $this->model()->$name = $value;
    }

    /**
     * @param $name
     * @return mixed
     */
    public function __get($name)
    {
        return $this->model()->$name;
    }

    private function __clone()
    {
    }
}