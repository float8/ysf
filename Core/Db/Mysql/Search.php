<?php
namespace Core\Db\Mysql;

use Core\Utils\Tools\Fun;

class Search
{
    /**
     * @desc 条件
     * @var null
     */
    private $where = null;
    /**
     * @desc 选项
     * @var array
     */
    private $options = [];
    /**
     * @desc $dao
     * @var Dao
     */
    private $dao = null;
    /**
     * @desc 操作符
     * @var array
     */
    private $operator = array('eq'=>'=','gt'=>'>','lt'=>'<','ge'=>'>=','le'=>'<=','in'=>'in','nin'=>'not in','neq'=>'<>','like'=>'like','llike'=>'like','rlike'=>'like','nlike'=>'not like','nllike'=>'not like','nrlike'=>'not like');
    /**
     * @desc 运算符
     * @var array
     */
    private $logical = array('and','or');
    /**
     * @desc 括号
     * @var array
     */
    private $bracket = array('lb'=>'(','rb'=>')');
    /**
     * @desc 条件的配置
     * @var array
     */
    private $whereCase = array('field'=>'','logical'=>'and','operator'=>'=','optKey'=>'eq','index'=>-1,'value'=>'?','lb'=>'','rb'=>'','bindValue'=>'');

    /**
     * @desc 创建where
     */
    public function getWhere()
    {
        $this->where = [];
        $where = $this->getOption('where', []);//获取where条件
        if(empty($where)) {
            return ;
        }
        $whereCase = [];
        foreach ($where as $k => $v) {
            if ($v === '' || is_null($v)) {//所以忽略此查询
                continue;
            }
            if (is_numeric($k)) {//当为数字key时直接作为whereCase处理
                $whereCase[] = [$v];
                continue;
            }
            $this->setWhereCase($whereCase, $k, $v);//设置条件
        }
        $this->composeWhereCase($whereCase);//合并条件
    }

    /**
     * @desc 清除特殊字符
     * @param $str
     * @return mixed
     */
    private function trim($str)
    {
        if (empty($str)) {
            return $str;
        }
        return str_replace(array("\r\n", "\r", "\n", "\t", ' ', '`'), '', $str);
    }

    /**
     * @desc 对wheres进行排序
     * @param $groups
     * @return array
     */
    private function sort($groups){
        if (empty($groups)) {
            return $groups;
        }
        if(isset($groups[-1])){
            $groups[] = $groups[-1];
            unset($groups[-1]);
        }
        $setWheres = function($group, &$list) {
            foreach ($group as $v){
                $list[] = $v;
            }
        };
        $list = [];
        foreach ($groups as $group){
            $setWheres($group, $list);
        }
        return $list;
    }

    /**
     * @desc 组合数组条件实例为字符串
     * @param $whereArr
     */
    private function composeWhereCase($whereArr)
    {
        if (empty($whereArr)) {
            return ;
        }
        $whereArr = $this->sort($whereArr);//排序
        $where = '';//where条件
        $bindValue = [];//绑定参数
        foreach ($whereArr as $k=>$v) {
            $_where = Fun::get($v, 'where', $v);
            if (isset($v['bindValue']) && !is_array($v['bindValue'])) {
                $bindValue[] = $v['bindValue'];//绑定参数
            } else if (isset($v['bindValue']) && is_array($v['bindValue'])) {
                foreach ($v['bindValue'] as $_v){
                    $bindValue[] = $_v;//绑定参数
                }
                $bindKey = str_repeat('?,', count($v['bindValue']));
                $bindKey = trim($bindKey, ',');
                $_where = str_replace('?', $bindKey, $_where);
            }
            $this->dao->bindSql($where, $_where);//绑定sql
        }
        $this->where = ['where'=>$where,'bindValue'=>$bindValue];
    }

    /**
     * @desc 创建字符串条件实例
     * @param $wheres
     * @param $field
     * @param $value
     */
    private function setWhereCase(&$wheres, $field, $value)
    {
        $field = explode('-', $field);
        $logical = array_flip($this->logical);
        $whereCase = $this->whereCase;
        $whereCase['bindValue'] = $value;

        foreach ($field as $case) {
            $case = strtolower($case);
            if (isset($logical[$case])) {//设置运算符
                $whereCase['logical'] = $case;
                continue;
            }
            if (isset($this->operator[$case])) {//设置操作符
                $whereCase['operator'] = $this->operator[$case];
                $whereCase['optKey'] = $case;
                continue;
            }
            if (is_numeric($case)) {//设置条件顺序
                $whereCase['index'] = intval($case);
                continue;
            }
            if (isset($bracket[$case])) {//设置括号
                $whereCase[$case] = $this->bracket[$case];
                continue;
            }
            $whereCase['field'] = $this->trim($case);//绑定字段
        }
        //生成where条件
        $where = $this->getWhereCase($whereCase);
        if (empty($where)) {
            return ;
        }
        //添加到where条件集合中
        if (isset($wheres[$whereCase['index']]) || $whereCase['index'] == -1) {
            $wheres[$whereCase['index']][] = $where;
        } else {
            $wheres[] = [$where];
        }
    }

    /**
     * @desc 获取条件
     * @param $whereCase
     * @return array|null
     */
    private function getWhereCase($whereCase)
    {
        $field = Fun::get($whereCase, 'field');
        $as = strpos($field, '.');
        if (empty($field) || ( $as === false && !$this->dao->existField($field))) {
            return null;
        }
        if($as === false) {
            $field = "`{$field}`";
        }
        $lb = Fun::get($whereCase, 'lb');
        $rb = Fun::get($whereCase, 'rb');
        $logical = Fun::get($whereCase, 'logical');
        $operator = Fun::get($whereCase, 'operator');
        $value = Fun::get($whereCase, 'value');
        $optKey = Fun::get($whereCase, 'optKey');
        $bindValue = Fun::get($whereCase, 'bindValue');
        $bindValue = $this->getBindValue($optKey, $bindValue);
        switch ($optKey) {
            case 'in':
            case 'nin':
                $where = "{$logical} {$lb}{$field} {$operator}({$value}){$rb}";
                break;
            case 'like'://两边模糊搜索
            case 'llike'://左侧模糊搜索
            case 'rlike'://右侧模糊搜索
            case 'nlike':
            case 'nllike':
            case 'nrlike':
                $where = "{$logical} {$lb}{$field} {$operator} {$value}{$rb}";
                break;
            default:
                $where = "{$logical} {$lb}{$field}{$operator}{$value}{$rb}";
                break;
        }
        return ['where'=>$where,'bindValue'=>$bindValue];
    }

    /**
     * @desc 获取绑定的参数
     * @param $optKey
     * @param $bindValue
     * @return bool|string
     */
    private function getBindValue($optKey, $bindValue)
    {
        switch ($optKey) {
            case 'like':
            case 'nlike'://两边模糊搜索
                $bindValue = "%{$bindValue}%";
                break;
            case 'llike':
            case 'nllike'://左侧模糊搜索
                $bindValue = "%{$bindValue}";
                break;
            case 'rlike':
            case 'nrlike'://右侧模糊搜索
                $bindValue = "{$bindValue}%";
                break;
        }
        return $bindValue;
    }

    /**
     * @desc 设置options
     * @param $key
     * @param $value
     * @return $this
     */
    public function setOption($key, $value)
    {
        $this->options[$key] = $value;
        return $this;
    }

    /**
     * @desc 获取选项
     * @param $key
     * @param mixed $default
     * @return mixed
     */
    public function getOption($key, $default = null)
    {
        return Fun::get($this->options, $key, $default);
    }

    /**
     * @desc 设置dao
     * @param Dao $dao
     * @return $this
     */
    public function setDao(Dao $dao)
    {
        $this->dao = $dao;
        return $this;
    }

    /**
     * @desc 设置offset
     */
    private function getOffset()
    {
        $size = $this->getOption('size', 10);
        $page = $this->getOption('page', 0);
        $page = intval($page);
        $offset = ($page < 1) ? 0 : ($page - 1);
        $offset = $offset * $size;
        return $offset;
    }

    /**
     * @desc 设置options
     * @param array $options
     * @return $this
     */
    public function setOptions(array $options)
    {
        $this->options = array_merge($this->options, $options);
        return $this;
    }

    /**
     * @param $params
     * @return string
     */
    private function getCountSql(&$params)
    {
        $sql = '';
        $params = Fun::get($this->where, 'bindValue', []);
        $this->dao->buildSelect();
        $this->dao->buildTable($this->getOption('table'));

        if(!empty($this->getOption('group'))){
            $this->dao->buildFields('count(DISTINCT '.$this->getOption('group').')');
        }else{
            $this->dao->buildFields('count(1)');
        }

        $this->dao->buildWhere(1);
        $this->dao->buildWhere(Fun::get($this->where, 'where'));
        $this->dao->build($sql, $params);
        return $sql;
    }

    /**
     * @param $params
     * @return string
     */
    private function getListSql(&$params)
    {
        $sql = '';
        $this->getWhere();
        $params = Fun::get($this->where, 'bindValue', []);
        $this->dao->buildSelect();
        $this->dao->buildTable($this->getOption('table'));
        $this->dao->buildFields($this->getOption('fields'));
        $this->dao->buildWhere(1);
        $this->dao->buildWhere(Fun::get($this->where, 'where'));
        $this->dao->buildGroup($this->getOption('group'));
        $this->dao->buildOrder($this->getOption('order'));
        $this->dao->buildLimit($this->getOffset(), $this->getOption('size', 10));
        $this->dao->build($sql, $params);
        return $sql;
    }

    /**
     * @desc 获取列表
     * @return mixed
     */
    public function getList()
    {
        if($this->getOption('list_sql', false)) {
            $this->dao->sql();
        }
        $params = [];
        $sql = $this->getListSql($params);
        return $this->dao->queryAll($sql, $params);
    }

    /**
     * @desc 获取总数
     * @return int
     */
    public function getTotal()
    {
        if($this->getOption('count_sql', false)) {
            $this->dao->sql();
        }
        $params = [];
        $sql = $this->getCountSql($params);
        return $this->dao->queryScalar($sql, $params);
    }

    /**
     * @return $this|self|\Redis
     */
    public static function getInstance()
    {
        static $obj = null;
        return empty($obj) ? ($obj = new static()) : $obj;
    }
}