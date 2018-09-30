<?php
/**
 * @desc:自动加载
 * @author: wanghongfeng
 * @date: 2017/10/30
 * @time: 上午10:39
 */
function __autoload($class)
{
    if(strpos($class, '\\') === false){
        return ;
    }
    $basePath = strpos($class, 'library') === 0 ? APP_PATH : SYSTEM_LIBRARY;
    $file = "{$basePath}/{$class}.php";
    $file = str_replace('\\', '/', $file);

    if (!file_exists($file)) {// 引入PHP文件
        throw new \Core\Base\Exception('file no exists:'.$file);
    }

    include $file;
}