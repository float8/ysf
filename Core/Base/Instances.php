<?php
/**
 * @desc: 实例化对象库
 * @author: wanghongfeng
 * @date: 2018/9/20
 * @time: 上午8:53
 */

namespace Core\Base;


use Core\Utils\Tools\Fun;

class Instances
{
    /**
     * @desc 资源对象
     * @var object
     */
    private static $resources = null;

    /**
     * @param array $sections
     * @return string
     */
    private static function sections(array $sections = [])
    {
        $section = implode('', $sections);
        $section = md5($section);
        return $section;
    }

    /**
     * @desc 设置对象
     * @param string $key
     * @param mixed $val
     * @param array $sections
     */
    public static function set(string $key, $val, array $sections = [])
    {
        if(empty($key) || empty($val)){
            return ;
        }

        if(empty($sections)){
            static::$resources[$key] = $val;
            return ;
        }
        $sections = self::sections($sections);
        static::$resources[$key][$sections] = $val;
    }

    /**
     * @desc 获取对象
     * @param string $key
     * @param array $sections
     * @return mixed
     */
    public static function get(string $key, array $sections = [])
    {
        if(empty($key)){
            return null;
        }
        $key = empty($sections) ? $key : $key.'.'.self::sections($sections);
        return Fun::get(static::$resources, $key);
    }

}
