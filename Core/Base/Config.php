<?php
/**
 * @desc:获取配置信息
 * @author: wanghongfeng
 * @date: 2017/10/30
 * @time: 下午2:01
 */

namespace Core\Base;

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
    public static function env($path, $default = null)
    {
        return self::get('env', $path, $default);
    }

    /**
     * @desc 根据模型获取信息
     * @param $path
     * @param $default
     * @return mixed
     */
    public static function module($path, $default = null)
    {
        return self::get('module', $path, $default);
    }

    /**
     * @desc 获取系统配置信息
     * @param $path
     * @param $default
     * @return mixed
     */
    public static function app($path, $default = null)
    {
        return self::get('app', $path, $default);
    }

    /**
     * @desc 获取公共配置信息
     * @param $path
     * @param $default
     * @return mixed
     */
    public static function common($path, $default = null)
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
    public static function get($type, $path, $default = null)
    {
        $path = trim($path, '.');
        if(empty($path)){
            throw new Exception('Invalid of the path');
        }
        //配置名称
        $name = $path;
        //keys
        $keys = [];
        //获取配置信息
        if(strpos($path, '.') !== false){
            //根据.拆分path
            $path = explode('.', $path);
            //无效的path数据格式
            $name = $path[0];
            //keys
            $keys = isset($path[1]) ? array_slice($path, 1) : $keys;
        }
        //配置信息
        $configs = self::getConfigs($type, $name);
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
    private static function getConfigs($type, $name)
    {
        static $configs = [];
        //缓存key
        $key = $type.$name;
        //获取配置信息
        if(isset($configs[$key])){
            return $configs[$key];
        }

        switch($type) {
            case 'env'://根据环境变量获取配置文件
                $file = APP_PATH.'/config/'.$name.'/'.__ENVIRON__.'.ini';
                break;
            case 'module'://根据模块获取配置文件
                $file = APP_PATH.'/config/'.$name.'/'.strtolower(__MODULE__) . '.ini';
                break;
            case 'app'://获取系统配置信息
                $configs[$key] = Application::app()->getConfig();
                return $configs[$key];
            case 'common'://公共配置文件
            default:
                $file = APP_PATH.'/config/'.$name.'.ini';
                break;
        }

        //yaf中拿取配置信息
        if(class_exists('Yaf\Config\Ini')) {
            $config = new Ini($file);
            $configs[$key] = $config->toArray();
            return $configs[$key];
        }

        //其它方式拿取配置信息
        if(!file_exists($file)) {
            throw new Exception("'{$name}' config file no exists");
        }

        //获取配置文件信息
        $configs[$key]  = parse_ini_file($file , true);

        return $configs[$key];
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
        foreach ($keys as $v){
            if(!isset($configs[$v])){
                return $default;
            } else {
                $configs = $configs[$v];
            }
        }
        return $configs;
    }
}