<?php
namespace Core\Base;

use Core\Utils\Api\ApiResult;

/**
 * Class Http
 * @package Core\Base
 */
abstract class Http
{
    /**
     * @return \Yaf\Request\Http
     */
    protected static function getRequest()
    {
        static $http = null;
        return !empty($http) ? $http : $http = new \Yaf\Request\Http();
    }

    /**
     * @desc ApiResult
     * @return ApiResult
     */
    protected static function ApiResult()
    {
        return ApiResult::getInstance();
    }


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