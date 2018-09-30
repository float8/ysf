<?php
/**
 * @desc:
 * @author: wanghongfeng
 * @date: 2017/12/18
 * @time: 上午10:39
 */

namespace Core\Utils\Tools;


class ClientServer
{
    /**
     * @desc 获取客户端访问端口
     * @return mixed
     */
    public static function getPort(){
        $serverKeys = [
            'HTTP_CLIENT_PORT',
            'REMOTE_PORT'
        ];
        foreach ($serverKeys as $v){
            if(!isset($_SERVER[$v])){
                continue;
            }
            return $_SERVER[$v];
        }
        return 0;
    }

    /**
     * @desc 获取客户端IP
     * @return string
     */
    public static function getIp()
    {
        $serverKeys = [
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        ];
        foreach ($serverKeys as $key) {
            if(!isset($_SERVER[$key])){
                continue;
            }
            $ips = explode(',', $_SERVER[$key]);
            foreach ($ips as $ip) {
                $ip = trim($ip);
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE)) {//过滤ip
                    return $ip;
                }
            }
        }
        return 'unknown';
    }

    /**
     * @desc 获取客户端uri
     * @return string
     */
    public static function getUri() {
        return isset($_SERVER['HTTP_CLIENT_URI']) ? $_SERVER['HTTP_CLIENT_URI'] : $_SERVER['REQUEST_URI'];
    }

    /**
     * @desc 获取客户端Scheme
     * @return string
     */
    public static function getScheme() {
        return isset($_SERVER['HTTP_CLIENT_SCHEME']) ? $_SERVER['HTTP_CLIENT_SCHEME'] : $_SERVER['REQUEST_SCHEME'];
    }

    /**
     * @desc 获取客户端host
     * @return string
     */
    public static function getHost() {
        return isset($_SERVER['HTTP_CLIENT_HOST']) ? $_SERVER['HTTP_CLIENT_HOST'] : $_SERVER['HTTP_HOST'];
    }

    /**
     * @desc 获取客户端url
     * @return string
     */
    public static function getUrl() {
        $url = self::getScheme();
        $url .= '://';
        $url .= self::getHost();
        $url .= self::getUri();
        return $url;
    }

}