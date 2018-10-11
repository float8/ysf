<?php
namespace Core\Db\Mysql;

/**
 * @see Dao
 * Class BuildSql
 * @package Core\Db\Mysql
 */
trait BuildSql
{
    /**
     * @desc sql
     * @var array
     */
    private $_sql = null;

    /**
     * @desc 创建字段
     * @param $fields
     * @return $this
     */
    public function buildFields($fields)
    {
        $this->_setSql('fields', $fields);
        return $this;
    }

    /**
     * @desc 创建表
     * @param $table
     * @return $this
     */
    public function buildTable($table)
    {
        $this->_setSql('table', $table);
        return $this;
    }

    /**
     * @desc Index Hints
     * @param $index_hint
     * @return $this
     */
    public function buildIndexHint($index_hint)
    {
        $this->_setSql('index_hint', $index_hint, 'push');
        return $this;
    }

    /**
     * @desc 创建where
     * @param $where
     * @param mixed $key
     * @param mixed $value
     * @return $this
     */
    public function buildWhere($where, $key = null, $value = null)
    {
        $this->buildValue($key, $value, $where);//绑定参数
        $this->_setSql('where', $where, 'push');
        return $this;
    }

    /**
     * @desc 创建 group by
     * @param $group
     * @return $this
     */
    public function buildGroup($group)
    {
        $this->_setSql('group', (!empty($group) ? "group by {$group}" : ''));
        return $this;
    }

    /**
     * @desc 创建 order by
     * @param $order
     * @return $this
     */
    public function buildOrder($order)
    {
        $this->_setSql('order', (!empty($order) ? "order by {$order}" : ''));
        return $this;
    }

    /**
     * @desc 创建limit
     * @param mixed $offset
     * @param int $size
     * @return $this
     */
    public function buildLimit($offset, $size = null)
    {
        if(is_null($offset) && is_null($size)){
            return $this;
        }
        if(is_null($size)){
            $this->_setSql('limit', 'limit '.intval($offset));
        } else if(is_null($offset) && !empty($size)) {
            $this->_setSql('limit', 'limit '.intval($size));
        } else if(!is_null($offset) && !empty($size)){
            $this->_setSql('limit', 'limit '.intval($offset).','.intval($size));
        }
        return $this;
    }

    /**
     * @desc 创建Value
     * @param mixed $key
     * @param mixed $value
     * @param string $where
     * @return $this
     */
    public function buildValue($key, $value = null, &$where = null)
    {
        if (empty($key) && $key !== 0) {
            return $this;
        }
        //in 参数绑定
        if (!empty($value) && is_array($value) && is_string($key)) {
            $key = trim($key, ':');
            $inBindKey = [];
            foreach ($value as $k => $v) {
                $bindKey = ":_{$key}_{$k}";
                $inBindKey[] = $bindKey;
                $this->_setSql(['params', $bindKey], $v );
            }
            $inBindKey = implode(',', $inBindKey);
            $where = str_replace("(:{$key})", "({$inBindKey})", $where);
            return $this;
        }
        //单参数绑定
        if (!is_array($key)) {
            $this->_setSql(['params', $key], $value);
            return $this;
        }
        //处理多个参数绑定
        foreach ($key as $k => $v) {
            $this->buildValue($k, $v);
        }
        return $this;
    }

    /**
     * @desc 绑定数据
     * @param $key
     * @param $value
     * @return $this
     */
    public function buildData($key, $value = null)
    {
        if (!empty($key) && is_array($key)) {
            $this->_setSql('data', $key, 'merge' );
        } else if (!empty($key) && is_string($key) && !is_null($value)) {
            $this->_setSql(['data', $key], $value );
        } else if (!empty($key) && is_string($key) && is_null($value)) {
            $this->_setSql('data', $key, 'push' );
        } else if(is_object($key)){
            $this->_setSql('data', (array)$key, 'merge' );
        }
        return $this;
    }

    /**
     * @desc 创建查询
     * @return $this
     */
    public function buildSelect()
    {
        $this->_setSql('type', 'select');
        return $this;
    }

    /**
     * @desc 创建update
     * @return $this
     */
    public function buildUpdate()
    {
        $this->_setSql('type', 'update');
        return $this;
    }

    /**
     * @desc 创建删除
     * @return $this
     */
    public function buildDelete()
    {
        $this->_setSql('type', 'delete');
        return $this;
    }

    /**
     * @desc 创建添加
     * @return $this
     */
    public function buildInsert()
    {
        $this->_setSql('type', 'insert');
        return $this;
    }

    /**
     * @desc 构建sql
     * @param string $sql
     * @param array $params
     * @return $this
     *
     * @uses _select()
     * @uses _update()
     * @uses _delete()
     * @uses _insert()
     */
    public function build(&$sql = null, &$params = null)
    {
        $this->buildValue($params);//绑定参数
        $this->buildToSql($sql);//创建sql

        $params = $this->_getSql('params', []);//获取绑定参数
        $this->_setLastSqlInfo($sql, $params);//设置最后一条sql信息
        return $this;
    }

    /**
     * @desc 创建sql
     * @param $sql
     */
    private function buildToSql(&$sql){
        if (!empty($sql)) {
            return ;
        }
        $type = $this->_getSql('type', 'select');//sql类型
        $method = "_{$type}";
        $this->$method($sql);//执行sql方法生成sql
    }

    /**
     * @desc 公共的绑定参数
     * @param $sql
     */
    private function _bindCommon(&$sql)
    {
        $this->bindSql($sql, $this->_mergeSql('where','where'));
        $this->bindSql($sql, $this->_getSql('group'));
        $this->bindSql($sql, $this->_getSql('order'));
        $this->bindSql($sql, $this->_getSql( 'limit'));
    }

    /**
     * @desc 帮定 update set
     * @param $sql
     * @return $this
     */
    private function _bindUpdateSet(&$sql)
    {
        $data = $this->_getSql('data', []);
        $this->filter($data);
        if (empty($data)) {
            $this->throw("Update data can't be empty!");
        }
        //绑定参数生成set0
        $sets = $params = [];
        foreach ($data as $column => $val) {
            if (is_string($column)) {
                $bindKey = ":_{$column}";
                $params[$bindKey] = $val;
                $sets[] = "`{$column}`={$bindKey}";
                continue;
            }
            if (is_numeric($column)) {
                $sets[] = $val;
            }
        }
        $this->bindSql($sql, 'set');
        $this->bindSql($sql, implode(',', $sets));
        $this->_setSql('params', $params, 'merge' );
        return $this;
    }

    /**
     * @desc 绑定插入Values
     * @param $sql
     * @return $this
     */
    private function _bindInsertValues(&$sql)
    {
        $data = $this->_getSql('data', []);
        //判断是否为批量插入
        if(!isset($data[0])) {
            $data = [$data];
        }
        $fieldsData = $data[0];
        $this->filter($fieldsData);
        if (empty($fieldsData)) {
            $this->throw("Insert data can't be empty!");
        }
        $fields = array_keys($fieldsData);
        $this->bindSql($sql, ' (`' . implode('`,`', $fields) . '`)');
        $this->bindSql($sql, 'values');
        /**
         * @desc 设置绑定数据
         * @param $index
         * @param $fields
         * @param $fieldsData
         * @param $params
         * @return array
         */
        $setBindData = function ($index, $fields, $fieldsData, &$params) {
            $bindKeys = [];
            foreach ($fields as $field) {
                $bindKeys[] = $bindKey = "{$field}_{$index}";
                $params[$bindKey] = isset($fieldsData[$field]) ? $fieldsData[$field] : '';
            }
            return $bindKeys;
        };
        //绑定参数
        $values = $params = [];
        foreach ($data as $index=>$fieldsData) {
            $fieldsData = is_object($fieldsData) ? (array)$fieldsData : $fieldsData;
            $bindKeys = $setBindData($index, $fields, $fieldsData, $params);
            $values[] = '(:' . implode(',:', $bindKeys) . ')';
        }
        $this->bindSql($sql, implode(',', $values));
        $this->_setSql('params', $params);
        return $this;
    }

    /**
     * @desc 获取表名
     * @return mixed
     */
    private function _getTable() {
        $table = $this->_getSql('table');//表名
        return $this->table($table);
    }

    /**
     * @desc 合并sql
     * @param $key
     * @param string $glue
     * @param string $prefix
     * @return string
     */
    private function _mergeSql($key, $prefix = '', $glue = ' '){
        $sql = $this->_getSql($key);
        if(empty($sql)){
            return '';
        }
        $sql = $prefix.' '.implode($glue, $sql);
        return $sql;
    }

    /**
     * @desc 获取字段
     * @return mixed
     */
    private function _getFields(){
        $fields = $this->_getSql('fields');//字段
        return $this->columns($fields);
    }

    /**
     * @desc 查询
     * @used-by build()
     * @param $sql
     */
    private function _select(&$sql)
    {
        $this->bindSql($sql, "select");
        $this->bindSql($sql, $this->_getFields());
        $this->bindSql($sql, "from");
        $this->bindSql($sql, $this->_getTable());
        $this->bindSql($sql, $this->_mergeSql('index_hint'));
        $this->_bindCommon($sql);
    }

    /**
     * @desc 修改
     * @used-by build()
     * @param $sql
     */
    private function _update(&$sql)
    {
        $this->bindSql($sql, "update");
        $this->bindSql($sql, $this->_getTable());
        $this->bindSql($sql, $this->_mergeSql('index_hint'));
        $this->_bindUpdateSet($sql);
        $this->_bindCommon($sql);
    }

    /**
     * @desc 删除
     * @used-by build()
     * @param $sql
     */
    private function _delete(&$sql)
    {
        $this->bindSql($sql, "delete from");
        $this->bindSql($sql, $this->_getTable());
        $this->bindSql($sql, $this->_mergeSql('index_hint'));
        $this->_bindCommon($sql);
    }

    /**
     * @desc 插入
     * @used-by build()
     * @param $sql
     */
    private function _insert(&$sql)
    {
        $this->bindSql($sql, "insert into");
        $this->bindSql($sql, $this->_getTable());
        $this->_bindInsertValues($sql);
    }

    /**
     * @desc 设置最后一条sql信息
     * @param $sql
     * @param $params
     */
    private function _setLastSqlInfo($sql, &$params)
    {
        $this->_setSql(null);
        $this->setLastSql($sql);
        $this->setLastParams($params);
    }

    /**
     * @desc 获取sql数据
     * @param $key
     * @param string $default
     * @return mixed
     */
    protected function _getSql($key, $default = '')
    {
        return isset($this->_sql[$key]) ? $this->_sql[$key] : $default;
    }

    /**
     * @desc 设置sql信息
     * @param $key
     * @param $value
     * @param string $action push/merge
     * @return $this
     */
    protected function _setSql($key, $value = null, $action = null)
    {
        //清空
        if(empty($key) && empty($value)){
            $this->_sql = null;
            return $this;
        }
        //action 不存在时
        if(is_null($action)){
            is_array($key) ? ($this->_sql[$key[0]][$key[1]] = $value) : ($this->_sql[$key] = $value);
            return $this;
        }
        //与当前变量值合并
        if($action == 'merge') {
            $this->_sql[$key] = array_merge($value, $this->_getSql($key, [])) ;
            return $this;
        }
        //push
        if (!isset($this->_sql[$key])) {
            $this->_sql[$key] = [];
        }
        $this->_sql[$key][] = $value;
        return $this;
    }
}