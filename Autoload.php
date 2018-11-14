<?php
/**
 * @desc:自动加载
 * @author: wanghongfeng
 * @date: 2017/10/30
 * @time: 上午10:39
 */

define('__ROOT_DIR__', dirname(__FILE__));

spl_autoload_register(function ($classname)
{
    $basedir = substr($classname, 0,-5);
    if( $basedir.'Model' == $classname && $basedir[-1] != '\\' ) {
        return include_once str_replace('\\', '/', __APP_DIR__.'/models/'.$basedir.'.php');
    }
    $basedir = substr($classname, 0,-6);
    if($basedir.'Parser' == $classname && $basedir[-1] != '\\') {
        return include_once str_replace('\\', '/', __APP_DIR__.'/parsers/'.$basedir.'.php');
    }
    include_once str_replace('\\', '/', __ROOT_DIR__.'/'.$classname.'.php');
});