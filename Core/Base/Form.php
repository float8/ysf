<?php
namespace Core\Base;
use Core\Utils\Tools\Validator;

/**
 * Class Form
 * @package Core\Base
 */
abstract class Form
{
    /**
     * @desc 过滤数据
     * @param array $fields
     * @param array $data
     * @return array
     */
    public function filter(array $fields, array $data = [])
    {
        $data = empty($data) ? $_POST : $data;
        if (empty($data) || empty($fields)) {
            return [];
        }
        $_data = [];
        foreach ($data as $k => $v) {
            if (isset($fields[$k])) {
                $_data[$k] = $v;
            }
        }
        return $_data;
    }

    /**
     * @desc 映射字段
     * @param array $map
     * @param array $data
     * @return array
     */
    public function map(array $map, array $data = [])
    {
        $data = empty($data) ? $_POST : $data;
        if (empty($data) || empty($map)) {
            return [];
        }
        /**
         * @desc 字段映射
         * @param $data
         * @param $map
         * @return array
         */
        $mapFields = function ($data, $map) {
            $_data = [];
            array_walk($data, function ($value, $key) use (&$_data, $map) {
                if (isset($map[$key])) {
                    $_data[$map[$key]] = $value;
                }
            });
            return $_data;
        };

        //映射一维数组
        if (!isset($data[0])) {
            return $mapFields($data, $map);
        }

        //映射二维数组
        foreach ($data as $key => $value) {
            $data[$key] = $mapFields($value, $map);
        }
        return $data;
    }

    /**
     * @desc 验证器
     */
    public function Validator()
    {
        return Validator::getInstance();
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