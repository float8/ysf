<?php
namespace Core\Base;
/**
 * Class Dao
 * @package Core\Base
 */
abstract class Dao
{

    /**
     * @desc 实例化子类
     * @return $this
     */
    public static function getInstance()
    {
        static $obj = null;
        return empty($obj) ? ($obj = new static()) : $obj;
    }
}