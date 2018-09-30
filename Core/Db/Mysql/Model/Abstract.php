<?php
namespace Core\Db\Mysql;

abstract class Model_Abstract implements \ArrayAccess
{
    public function __construct(array $data = [])
    {
        if (empty($data)) {
            return ;
        }
        foreach ($data as $key => $value) {
            $this->$key = $value;
        }
    }

    public function offsetSet($offset, $value)
    {
        $this->$offset = $value;
    }

    public function offsetExists($offset)
    {
        return isset($this->$offset);
    }

    public function offsetUnset($offset)
    {
        unset($this->$offset);
    }

    public function offsetGet($offset)
    {
        return isset($this->$offset) ? $this->$offset : null;
    }

    public function toArray()
    {
        return array($this);
    }

    public function __set($name, $value)
    {
        $this->$name = $value;
    }

    public function __get($name)
    {
        return isset($this->$name) ? $this->$name : null;
    }

    public function __call($name, array $arguments)
    {

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