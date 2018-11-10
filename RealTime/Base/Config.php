<?php
/**
 * @desc:
 * @author: wanghongfeng
 * @date: 2018/10/24
 * @time: 下午7:24
 */

namespace RealTime\Base;

use Core\Utils\Tools\Fun;

class Config
{

    /**
     * @desc 配置信息
     * @var array
     */
    private static $config = [];

    /**
     * @desc 初始化配置信息
     * @param $configFile
     */
    public static function config($configFile)
    {
        file_exists($configFile) or trigger_error('The application configuration file does not exist', E_USER_ERROR);

        $configs = parse_ini_file($configFile, true);
        if(empty($configs)) {
            return ;
        }
        $sectionVal = Fun::get($configs, 'swoole', []);
        $envSectionVal = Fun::get($configs, __ENVIRON__.':'.'swoole', []);
        $sectionVal = array_merge($sectionVal, $envSectionVal);
        //解析配置信息
        foreach ($sectionVal as $key=>$value){
            self::parseConfig($key, $value);
        }
    }

    /**
     * @desc 解析config
     * @param $key
     * @param $value
     */
    private static function parseConfig($key, $value)
    {
        $config = &self::$config;
        $keys = explode('.', $key);
        foreach ($keys as $key) {
            $config = &$config[$key];
        }
        $config = $value;
    }

    /**
     * @desc 获取配置信息
     * @return array
     */
    public static function getConfig()
    {
        return self::$config;
    }
}