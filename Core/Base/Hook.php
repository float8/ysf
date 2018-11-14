<?php
/**
 * @desc: 钩子
 * @author: wanghongfeng
 * @date: 2018/9/20
 * @time: 下午6:51
 */

namespace Core\Base;

class Hook
{
    /**
     * @desc app hooks
     * @var array
     */
    private static $appHooks = [];

    /**
     * @desc 系统的hooks命名空间
     * @var string
     */
    private static $sysNamespace = '\Core\Hooks\\';

    /**
     * @desc app hook 的命名空间
     * @var string
     */
    private static $appNamespace = '\Hooks\\';

    /**
     * @desc 设置系统的命名空间
     * @param $nmespace
     */
    public static function setSysNameSpace($nmespace){
        self::$sysNamespace = $nmespace;
    }

    /**
     * @desc 设置APP的命名空间
     * @param $nmespace
     */
    public static function setAppNameSpace($nmespace){
        self::$appNamespace = $nmespace;
    }

    /**
     * @desc 初始化钩子
     */
    public static function init()
    {
        $hooks = Config::app('app.hooks');//app钩子
        if(!empty($hooks)) {//注册的钩子
            self::$appHooks = explode(',', $hooks);
            self::$appHooks = array_flip(self::$appHooks);
        }
    }

    /**
     * @desc 触发一个钩子
     * @param string $hookName 钩子的名称，与钩子文件名称保持一致，允许/
     * @param string $method 钩子方法
     * @param array $data 钩子的入参
     * @return mixed
     */
    public static function trigger(string $hookName, string $method, array $data = [])
    {
        $namespace = isset(self::$appHooks[$hookName]) ?
                        self::$appNamespace :
                        self::$sysNamespace;
        return call_user_func([$namespace.$hookName, $method], $data);
    }
}