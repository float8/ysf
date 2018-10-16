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
     * @desc 系统hooks
     * @var array
     */
    private static $sysHooks = [];

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
     * @desc 翻转钩子
     */
    public static function reverseHooks()
    {
        static $revers = false;
        if($revers) {
            return ;
        }
        $revers = true;
        //系统钩子
        self::$sysHooks = array_flip(self::$sysHooks);
        //app钩子
        $hooks = Config::app('yaf.app.hooks');
        //注册的钩子
        if(!empty($hooks)) {
            self::$appHooks = explode(',', $hooks);
            self::$appHooks = array_flip(self::$appHooks);
        }
    }

    /**
     * @desc 触发一个钩子
     * @param string $hookName 钩子的名称，与钩子文件名称保持一致，允许/
     * @param string $method 钩子方法
     * @param array $data 钩子的入参
     * @return bool|mixed
     */
    public static function trigger(string $hookName, string $method, array $data = [])
    {
        //执行app钩子
        if(self::existAppHook($hookName)){
            return call_user_func([self::$sysNamespace.self::$appHooks[$hookName], $method], $data);
        }
        //执行系统钩子
        return call_user_func([self::$appNamespace.$hookName, $method], $data);
    }

    /**
     * @desc 判断是否存在APP钩子
     */
    public static function existAppHook($hookName)
    {
        self::reverseHooks();//对钩子数据进行key value 对调
        return isset(self::$appHooks[$hookName]);
    }

    /**
     * @desc 触发app的钩子
     * @param string $hookName
     * @param string $method
     * @param array $data
     * @return mixed
     */
    public static function triggerApp(string $hookName, string $method, array $data = [])
    {
        return call_user_func([self::$appNamespace.$hookName.'Model', $method], $data);
    }
}