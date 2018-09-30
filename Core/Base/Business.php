<?php
namespace Core\Base;
/**
 * @desc 业务层基类
 * Class Business
 * @package Core\Base
 */
abstract class Business
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