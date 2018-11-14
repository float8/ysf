<?php
/**
 * @desc:
 * @author: wanghongfeng
 * @date: 2018/10/25
 * @time: 下午3:31
 */

namespace RealTime\Base;


class Loader
{
    /**
     * @desc 事件的加载器
     * @param string $name
     * @param string $module
     * @return bool|object
     */
    public static function swoole($name, $module = null)
    {
        return self::load('swoole', 'Event', $name, $module);
    }

    /**
     * @desc 模型加载
     * @param string $name
     * @param string $module
     * @return bool|object
     */
    public static function module($name, $module)
    {
        return self::load('modules', 'Event', $name, $module);
    }

    /**
     * @desc controller 加载器
     * @param $type
     * @param $name
     * @param $suffix
     * @param $module
     * @return bool|object
     */
    public static function controller($type, $name, $suffix, $module = null)
    {
        return self::load('modules/'.$module.'/'.$type, $suffix, $name);
    }

    /**
     * @desc action 加载器
     * @param $file
     * @param $name
     * @param $suffix
     * @param $module
     * @return bool
     */
    public static function action($file, $name, $suffix, $module)
    {
        static $instances = [];
        if(isset($instances[$module][$name])){
            return $instances[$module][$name];
        }
        $file .= '.php';
        //文件不存在不处理
        if(!file_exists($file)){
            return false;
        }
        include_once $file; //引入文件
        $className = $name.$suffix;
        return $instances[$module][$name] = new $className();
    }

    /**
     * @desc 加载文件
     * @param string $type
     * @param string $suffix
     * @param string $name
     * @param string $module
     * @return bool|object
     */
    private static function load($type, $suffix, $name, $module = null)
    {
        static $instances = [];
        //如果存在实例则返回
        if($instance = self::getInstance($instances, $type, $name, $module)) {
            return $instance;
        }
        $baseDir = __APP_DIR__.$type.'/';
        $file = empty($module) ? $baseDir.$name.'.php' : $baseDir.$module.'/'.$name.'.php';
        //文件不存在不处理
        if(!file_exists($file)){
            return false;
        }
        include_once $file; //引入文件
        $className = $name.$suffix;
        $instance = new $className();
        //无模型
        if(empty($module)) {
            return $instances[$type][$name] = $instance;
        }
        //存在模型
        return $instances[$type][$module][$name] = $instance;
    }

    /**
     * @desc 获取实例
     * @param $instances
     * @param $type
     * @param $name
     * @param $module
     * @return bool
     */
    private static function getInstance($instances, $type, $name, $module = null)
    {
        if(empty($instances)) {
            return false;
        }
        //无模块的
        if(empty($module) && isset($instances[$type][$name])) {
            return $instances[$type][$name];
        }
        //模块
        if(empty($module) && isset($instances[$type][$module][$name])) {
            return $instances[$type][$module][$name];
        }
        return false;
    }
}