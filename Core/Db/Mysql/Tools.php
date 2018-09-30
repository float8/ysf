<?php
namespace Core\Db\Mysql;

use Core\Base\Config;
use Core\Base\Log;

/**
 * @see Dao
 * Class Tools
 * @package Core\Db\Mysql
 */
trait Tools {

    /**
     * @desc 开始执行时间
     * @var int
     */
    private $_startTime = 0;

    /**
     * @desc 记录开始时间
     */
    public function _startTime(){
        $open_log = Config::app('yaf.app.mysql.open_log', true);
        if($open_log){
            $this->_startTime = microtime(true);
        }
    }

    /**
     * @desc 写日志
     */
    public function _writeLog(){
        $open_log = Config::app('yaf.app.mysql.open_log', true);//是否打开日志
        $time_out = (float)Config::app('yaf.app.mysql.time_out', 0.5);//超时间
        $runtime = microtime(true) - $this->_startTime;//sql执行时间
        if ($runtime > $time_out) {//记录超时日志
            $time_out = $runtime - $time_out;
            Log::write(LOG_WARNING, "Execute-Time: {$runtime} Time-Out: {$time_out} Execute-SQL: ".$this->getLastSql(), ['line'=>'mysql']);
        }
        if($open_log) {//记录日志
            Log::write(LOG_INFO, "Execute-Time: {$runtime} Execute-SQL: ".$this->getLastSql(), ['line'=>'mysql']);
        }
    }

    /**
     * @desc 绑定sql
     * @param $sql
     * @param $sqlStr
     * @return $this
     */
    public function bindSql(&$sql, $sqlStr) {
        if(!empty($sqlStr)) {
            $sql .= (empty($sqlStr) ? '' : ' ').trim($sqlStr, ' ');
        }
        return $this;
    }

    /**
     * @desc 绑定参数
     * @param $params
     * @param $key
     * @param $value
     * @return $this
     */
    public function bindArray(&$params, $key, $value = null) {
        if(!empty($key) && !is_array($key)) {
            $params[$key] = $value;
        } else if(!empty($key) && is_array($key)) {
            $params = array_merge($params, $key);
        }
        return $this;
    }

    /**
     * @desc 判断字段是否存在
     * @param $field
     * @return bool
     */

    public function existField($field ) {
        static $fields = null;
        if(empty($field) || !is_string($field)) {
            return false;
        }
        if(empty($fields)) {
            $fields = array_flip($this->_fields);
        }
        return isset($fields[$field]);
    }

    /**
     * @desc 获取查询字段
     * @param string|array $fields
     * @return string
     */
    public function columns($fields = null) {
        if(!empty($fields) && is_string($fields)) {
            return $fields;
        }
        $build = function($fields) {
            return  '`'.implode('`,`', $fields).'`';
        };
        if(empty($fields)) {
            return $build($this->_fields);
        }
        if(is_array($fields)) {
            foreach ($fields as $key=>$field) {
                if(!$this->existField($field)) {
                    unset($fields[$key]);
                }
            }
        }
        if(empty($fields)) {
            return $build($this->_fields);
        }
        return is_array($fields) ? $build($fields) : '';
    }

    /**
     * @desc 获取 pdo sql
     * @param string $sql
     * @param array $params
     * @return mixed|string
     */
	protected function getPdoSql($sql, $params)
    {
        if (empty($sql)) {
            return $sql;
        }
        $sql = str_replace('	', ' ', $sql);
        $sql = str_replace("\n", ' ', $sql);
        $sql = preg_replace("/\s(?=\s)/", "\\1", $sql);
        $sql = trim($sql, ' ');
        if (empty($params)) {
            return $sql;
        }
        //创建pdo sql ？
        $_getPdoSql1 = function ($sql, &$params) {
            $sqlArr = explode('?', $sql);
            $realSql = "";
            foreach ($sqlArr as $k => $v) {
                $bindValue = '';
                if (isset($params[$k + 1])) {
                    $bindValue = '"' .addslashes($params[$k + 1]). '"';
                    unset($params[$k + 1]);
                }
                $realSql .= $v.$bindValue;
            }
            return $realSql;
        };
        //创建pdo sql :字段
        $_getPdoSql2 = function ($sql, $params) {
            $search = $replace = [];
            uksort($params, function ($a, $b){
                return -strnatcmp($a, $b);
            });
            foreach ($params as $k => $v) {
                array_push($search, $k);
                array_push($replace, '"' . addslashes($v) . '"');
            }
            return str_replace($search, $replace, $sql);
        };
        $sql = $_getPdoSql1($sql, $params);
        $sql = $_getPdoSql2($sql, $params);
        return $sql;
    }

    /**
     * @desc 过滤字段
     * @param $data
     * @return $this
     * @throws \Exception
     */
	public function filter( &$data ) {
		if(empty($data)) {
			return $this;
		}
		if(empty($this->_fields)) {
			throw new \Exception('请设置"_fields"属性');
		}
		if(is_object($data)){
            $data = (array)$data;
        }
		if(is_string($data)) {
			$data = $this->existField($data) ? $data : null;
			return $this;
		}
		if(is_array($data)) {
			$_data = [];
			foreach ($data as $k=>$v) {
				if(is_string($k) && !$this->existField($k)) {
					continue;
				}
				$_data[$k] = $v;
			}
            if(!empty($_data)) {
                $data = $_data;
            }
		}
		return $this;
	}

    /**
     * @desc 字段映射处理
     * @param mixed $data
     * @return $this
     * @throws \Exception
     */
	public function map( &$data ) {
		if(empty($data)) {
			return $this;
		}
		if(empty($this->_map)) {
			throw new \Exception('请设置"_map"属性');
		}
		if(is_string($data)) {
			$data = isset($this->_map[$data]) ? $this->_map[$data] : null;
			return $this;
		}
		if(is_array($data)) {
			$_data = array();
			foreach ($data as $k=>$v) {
				$fields = isset($this->_map[$k]) ? $this->_map[$k] : null;
				$_data[$fields] = $v;
			}
			$data = $_data;
		}
		return $this;
	}

    /**
     * @desc 自动生成dao属性
     * @param bool $echo
     * @return string
     */
    public function _fields($echo = true) {
        $table = $this->table();
		$sql = /** @lang sql */
            "select column_name,column_key from information_schema.`columns` where table_name = '{$table}'";
		$result = $this->queryAll($sql, null, false);
		$_fields = [];
		$_pk = '';
		foreach ($result as $v) {
			$_fields[] = $v['column_name'];
			$_pk = $v['column_key'] == 'PRI' ? $v['column_name'] : $_pk;
		}
		$_fields = implode('\',\'', $_fields);
		$code =  <<<code
	/**
	 * @desc 表名
	 * @var string
	 */
	public \$_table = '{$table}';
	/**
	 * @desc 主键
	 * @var string
	 */
	public \$_pk = '{$_pk}';
	/**
	 * @desc 表字段
	 * @var array
	 */
	public \$_fields = ['{$_fields}'];
code;
		if($echo) {
            echo '<pre>'.$code;
		}

		return $code;
	}
}