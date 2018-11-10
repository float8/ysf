<?php
/**
 * @desc:自动加载
 * @author: wanghongfeng
 * @date: 2017/10/30
 * @time: 上午10:39
 */

spl_autoload_register(function ($classname)
{
    $basePath = substr($classname, -5) == 'Model' ?
                \Core\Base\Config::app('swoole.directory').'/models/' :
                dirname(__FILE__) .'/' ;
    include_once str_replace('\\', '/', $basePath.$classname.'.php');
});