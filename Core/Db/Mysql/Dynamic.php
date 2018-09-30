<?php
namespace Core\Db\Mysql;
/**
 * Trait Dynamic
 * @package Core\Db\Mysql
 */
trait Dynamic
{

    /**
     * @desc 创建方法
     * @param $name
     * @param $arguments
     * @return mixed
     */
    public function createMethod($name, $arguments)
    {
        //判断是否存在
        if (strpos($name, "isBy") === 0) {
            return self::isByCall($name, $arguments);
        }
        //根据某个字段获取信息
        if (strpos($name, "getBy") === 0) {
            return self::getByCall($name, $arguments);
        }
        //根据某个字段获取信息 不适用于all作为字段
        if (strpos($name, "getAllBy") === 0) {
            return self::getAllByCall($name, $arguments);
        }
        //根据某个字段获取某个字段的信息 不适用与By作为字段
        if (strpos($name, "getBy") === false && strpos($name, "get") === 0 && strpos($name, "By") > 0) {
            return self::getFieldByCall($name, $arguments);
        }
        return null;
    }

    /**
     * @desc 调用getFieldBy
     * @param $name
     * @param $arguments
     * @return mixed
     */
    protected function getFieldByCall($name, $arguments)
    {
        $fields = explode("By", substr($name, 3));
        preg_match_all("/([a-zA-Z]{1}[a-z]*)?[^A-Z]/", $fields[0], $select) or $this->throw("Method \"{$name}\" cannot contain special characters");
        preg_match_all("/([a-zA-Z]{1}[a-z]*)?[^A-Z]/", $fields[1], $field) or $this->throw("Method \"{$name}\" cannot contain special characters");

        $select = strtolower(implode('_', $select[0])) or $this->throw("\"getFieldBy\" not have select");
        $field = strtolower(implode('_', $field[0])) or $this->throw("\"getFieldBy\" not have field");

        $args = array_merge(array($field), $arguments, array($select));

        return call_user_func_array(array($this, 'getFieldBy'), $args);
    }

    /**
     * @desc 获取某个字段值
     * @param $name
     * @param $value
     * @param $fields
     * @param null $table
     * @return mixed
     */
    private function getFieldBy($name, $value, $fields, $table = null)
    {
        $table = $this->table($table);
        $fields = trim($fields, '`');
        return $this->queryScalar(function (Dao $dao) use ($table, $fields, $name, $value) {
            $dao->buildTable($table);
            $dao->buildFields([$fields]);
            $dao->buildWhere("`{$name}`=:{$name}", ":{$name}", $value);
            $dao->buildLimit(1);
        });
    }

    /**
     * @desc 调用getAllBy
     * @param $name
     * @param $arguments
     * @return mixed
     */
    protected function getAllByCall($name, $arguments)
    {
        $field = str_replace("getAllBy", "", $name) or $this->throw("\"getAllBy\" not have field");
        preg_match_all("/([a-zA-Z]{1}[a-z]*)?[^A-Z]/", $field, $array) or $this->throw("Method \"{$name}\" cannot contain special characters");
        $field = strtolower(implode('_', $array[0]));
        $args = array_merge(array($field), $arguments);
        return call_user_func_array(array($this, 'getAllBy'), $args);
    }

    /**
     * @desc 获取一条数据
     * @param $name
     * @param $value
     * @param string $fields
     * @param null $table
     * @return mixed
     */
    protected function getAllBy($name, $value, $fields = '*', $table = null)
    {
        $table = $this->table($table);
        $fields = $this->columns($fields);
        return $this->queryAll(function (Dao $dao) use ($table, $fields, $name, $value) {
            $dao->buildTable($table);
            $dao->buildFields($fields);
            $dao->buildWhere("`{$name}`=:{$name}", ":{$name}", $value);
            $dao->buildLimit(1);
        });
    }

    /**
     * @desc 调用getBy
     * @param $name
     * @param $arguments
     * @return mixed
     */
    protected function getByCall($name, $arguments)
    {
        $field = str_replace("getBy", "", $name) or $this->throw("\"getBy\" not have field");
        preg_match_all("/([a-zA-Z]{1}[a-z]*)?[^A-Z]/", $field, $array) or $this->throw("Method \"{$name}\" cannot contain special characters");
        $field = strtolower(implode('_', $array[0]));
        $args = array_merge(array($field), $arguments);
        return call_user_func_array(array($this, 'getBy'), $args);
    }

    /**
     * @desc 获取一条数据
     * @param $name
     * @param $value
     * @param string $fields
     * @param null $table
     * @return mixed
     */
    protected function getBy($name, $value, $fields = '*', $table = null)
    {
        $table = $this->table($table);
        $fields = $this->columns($fields);
        return $this->queryRow(function (Dao $dao) use ($table, $fields, $name, $value) {
            $dao->buildTable($table);
            $dao->buildFields($fields);
            $dao->buildWhere("`{$name}`=:{$name}", ":{$name}", $value);
            $dao->buildLimit(1);
        });
    }

    /**
     * @desc 调用isBy
     * @param $name
     * @param $arguments
     * @return mixed
     */
    protected function isByCall($name, $arguments)
    {
        $field = str_replace("isBy", "", $name) or $this->throw("\"isBy\" not have field");
        preg_match_all("/([a-zA-Z]{1}[a-z]*)?[^A-Z]/", $field, $array) or $this->throw("Method \"{$name}\" cannot contain special characters");
        $field = strtolower(implode('_', $array[0]));
        $args = array_merge(array($field), $arguments);
        return call_user_func_array(array($this, 'isBy'), $args);
    }

    /**
     * @desc 是否存在数据
     * @param $name
     * @param $value
     * @param null $table
     * @return mixed
     */
    private function isBy($name, $value, $table = null)
    {
        $table = $this->table($table);
        return $this->queryScalar(function (Dao $dao) use ($table, $name, $value) {
            $dao->buildTable($table);
            $dao->buildFields('count(1)');
            $dao->buildWhere("`{$name}`=:{$name}", ":{$name}", $value);
            $dao->buildLimit(1);
        });
    }
}