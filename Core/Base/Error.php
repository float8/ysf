<?php
namespace Core\Base;
/**
 * Class Error
 * @package Core\Base
 */
abstract class Error
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