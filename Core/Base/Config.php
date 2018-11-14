<?php
/**
 * @desc:获取配置信息
 * @author: wanghongfeng
 * @date: 2017/10/30
 * @time: 下午2:01
 */

namespace Core\Base;

use Core\Utils\Tools\Fun;
use Exception;
use Yaf\Application;
use Yaf\Config\Ini;

class Config
{
    /**
     * @desc 根据环境获取信息
     * @param $path
     * @param $default
     * @return mixed
     */
    public static function env($path = null, $default = null)
    {
        return self::get('env', $path, $default);
    }

    /**
     * @desc 根据模型获取信息
     * @param $path
     * @param $default
     * @return mixed
     */
    public static function module($path = null, $default = null)
    {
        return self::get('module', $path, $default);
    }

    /**
     * @desc 获取系统配置信息
     * @param $path
     * @param $default
     * @return mixed
     */
    public static function app($path = null, $default = null)
    {
        return self::get('app', $path, $default);
    }

    /**
     * @desc 获取公共配置信息
     * @param $path
     * @param $default
     * @return mixed
     */
    public static function common($path = null, $default = null)
    {
        return self::get('common', $path, $default);
    }

    /**
     * @desc 获取配置信息
     * @param $type [common|env|module|app]
     * @param $path
     * @param $default
     * @return mixed
     * @throws Exception
     */
    private static function get($type, $path = null, $default = null)
    {
        $path = trim($path, '.');
        //获取类型的所有配置信息
        if(empty($path)){
            return self::getConfigs($type);
        }
        $isKeys = strpos($path, '.') !== false;
        //单个key,并且不是app的时候获取配置信息
        if($type != 'app' && !$isKeys) {
            return self::getConfigs($type, $path);
        }
        //单个key,并且是app的时候获取配置信息
        if($type == 'app' && !$isKeys) {
            return Fun::get(self::getConfigs($type), $path, $default);
        }
        //根据.拆分path
        $path = explode('.', $path);
        //无效的path数据格式
        $name = $type == 'app' ? null : $path[0];
        //获取所有配置
        $configs = self::getConfigs($type, $name);
        //keys
        $keys = $type == 'app' ? $path : array_slice($path, 1);
        //获取配置信息
        return self::getConfig($configs, $keys, $default);
    }

    /**
     * @desc 获取某类型config
     * @param $type
     * @param $name
     * @return mixed
     * @throws Exception
     */
    private static function getConfigs($type, $name = null)
    {
        static $configs = [];
        //获取此类型的所有配置信息
        if(empty($name) && isset($configs[$type])) {
            return $configs[$type];
        }
        //获取某类型下的配置信息
        if(!empty($name) && isset($configs[$type][$name])) {
            return $configs[$type][$name];
        }
        //应用配置文件
        if($type == 'app') {
            $configs[$type] = (APP_EXT == 'yaf') ?
                                Application::app()->getConfig()->toArray() :
                                \RealTime\Base\Config::getConfig();
            return $configs[$type];
        }
        $fileName = '';//文件名称
        $ext = '.ini';//文件扩展
        $baseDir = APP_PATH.'/config/'.$name;//文件的基础路径
        switch($type) {
            case 'env'://根据环境变量获取配置文件
                $fileName = DIRECTORY_SEPARATOR.__ENVIRON__;
                break;
            case 'module'://根据模块获取配置文件
                $fileName = DIRECTORY_SEPARATOR.__MODULE__;
                break;
        }
        //配置文件
        $file = $baseDir.strtolower($fileName).$ext;
        //其它方式拿取配置信息
        if(!file_exists($file)) {
            throw new Exception("'{$name}' config file no exists");
        }
        //yaf中拿取配置信息
        if(class_exists('Yaf\Config\Ini')) {
            $config = new Ini($file);
            $configs[$type][$name] = $config->toArray();
            return $configs[$type][$name];
        }
        //获取配置文件信息
        $configs[$type][$name]  = parse_ini_file($file , true);
        return $configs[$type][$name];
    }

    /**
     * @desc 获取某一个配置
     * @param $configs
     * @param $keys
     * @param $default
     * @return mixed
     */
    private static function getConfig($configs, $keys, $default)
    {
        if(empty($configs) || empty($keys)){
            return $configs;
        }
        //key为数组时
        foreach ($keys as $key){
            if(!isset($configs[$key])){
                return $default;
            }
            $configs = $configs[$key];
        }
        return $configs;
    }
}