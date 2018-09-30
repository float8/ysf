<?php
namespace Core\Utils\Tools;

class Fun
{
    /**
     * @desc 获取数据
     * @param $params
     * @param $key
     * @param string $default
     * @return mixed
     */
    public static function get( $params, $key, $default = null ){
        $key = trim($key, '.');
        if(strpos($key, '.') === false) {
            return is_object($params) ?
                ( isset($params->$key) ? $params->$key : $default ) :
                ( isset($params[$key]) ? $params[$key] : $default );
        }
        $key = explode('.', $key);
        foreach ($key as $v) {
            if( is_object($params) ? !isset($params->$v) : !isset($params[$v]) ) {
                return $default;
            } else {
                $params = is_object($params->$v) ? $params->$v : $params[$v];
            }
        }
        return $params;
    }

    /**
     * @desc 是否成功
     * @param array $result
     * @return boolean
     */
    public static function isSucceed($result)
    {
        if(empty($result) || !isset($result['code']) ) {
            return false;
        }
        return substr($result['code'], -3) == 200 ? true : false;
    }

    /**
     * @desc 获取服务器IP
     * @return mixed
     */
    public static function getServerIp() {
        if (substr(PHP_SAPI, 0, 3) == 'cli') {
            exec('/sbin/ifconfig eth0 | sed -n \'s/^ *.*addr:\\([0-9.]\\{7,\\}\\) .*$/\\1/p\'', $arr);
            return $arr[0];
        } else if(isset($_SERVER['SERVER_ADDR']) && !empty($_SERVER['SERVER_ADDR'])) {
            return $_SERVER['SERVER_ADDR'];
        } else if(isset($_SERVER['LOCAL_ADDR']) && !empty($_SERVER['LOCAL_ADDR'])) {
            return $_SERVER['LOCAL_ADDR'];
        }
        return getenv('SERVER_ADDR');
    }

    /**
     * @desc 生成短连接
     * @param $url
     * @return string
     */
    public static function shortLink($url) {
        $codeStr = sprintf('%u', crc32($url));
        $surl = '';
        while($codeStr){
            $mod = $codeStr % 62;
            if($mod>9 && $mod<=35){
                $mod = chr($mod + 55);
            }elseif($mod>35){
                $mod = chr($mod + 61);
            }
            $surl .= $mod;
            $codeStr = floor($codeStr/62);
        }
        return $surl;
    }

}