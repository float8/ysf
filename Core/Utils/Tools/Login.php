<?php
/**
 * @desc:登录
 * @author: wanghongfeng
 * @date: 2017/10/19
 * @time: 下午3:50
 */
namespace Core\Utils\Tools;

use Core\Base\Config;

/**
 * Class Login
 * @package Core\Utils\Tools
 */
class Login
{
    /**
     * @desc 规定 cookie 的有效期。
     * @var null|int
     */
    private $expire = null;

    /**
     * @desc 规定 cookie 的服务器路径。
     * @var string
     */
    private $path = '/';

    /**
     * @desc 规定 cookie 的域名。
     * @var string
     */
    private $domain = null;

    /**
     * @desc cookie 名字
     * @var string
     */
    private $name = null;

    /**
     * @desc ip
     * @var int
     */
    private $ip = null;

    /**
     * @desc 浏览器信息
     * @var string
     */
    private $userAgent = null;

    public function __construct()
    {
        $login = Config::app('yaf.app.login');

        $this->ip = ClientServer::getIp();
        $this->ip = ip2long($this->ip);

        $this->userAgent = Fun::get($_SERVER, 'HTTP_USER_AGENT');
        $this->userAgent = md5($this->userAgent);

        $this->name = Fun::get($login, 'name', 'login');
        $this->expire = Fun::get($login, 'expire');
        $this->path = Fun::get($login, 'path', '/');
        $this->domain = Fun::get($login, 'domain');
    }

    /**
     * @desc 获取cookie信息并解析
     * @return mixed
     */
    public function get()
    {
        static $uid = null;
        if (!empty($uid)) {
            return $uid;
        }
        $value = Fun::get($_COOKIE, $this->name);
        if (empty($value)) {
            $this->delete();
            return null;
        }
        $value = explode('-', $value);
        if (count($value) != 5) {
            $this->delete();
            return null;
        }
        $data = [
                'token' => $value[0],
                'uid' => $value[1],
                'userAgent' => $value[2],
                'ip' => $value[3],
                'expire' => $value[4],
        ];
        //验证token
        if (!$this->verifyToken($data)) {
            $this->delete();
            return null;
        }
        return $value[1];
    }

    /**
     * @desc 验证token
     * @param $value
     * @return bool
     */
    private function verifyToken($value)
    {
        $data = [
            'ip' => $this->ip,
            'iuserAgentp' => $this->userAgent,
            'uid' => $value['uid'],
            'expire' => $value['expire'],
        ];
        $token = $this->createPassword($data);
        if (
            !password_verify($token, $value['token']) || //token验证失败
            (!empty($data['expire']) && intval($data['expire']) - time() <= 0) //cookie已过期
         ) {
            $this->delete();
            return false;
        }

        return true;
    }

    /**
     * 设置cookie
     * @param $uid
     * @return bool
     */
    public function setCookie($uid)
    {
        $data = [
            'uid' => $uid,
            'expire' => $this->expire,
            'ip' => $this->ip,
            'userAgent' => $this->userAgent
        ];

        $data['token'] = password_hash($this->createPassword($data), PASSWORD_DEFAULT);

        return setcookie($this->name, $this->createValue($data), $this->expire, $this->path, $this->domain);
    }

    /**
     * @desc 生成token
     * @param $data
     * @return string
     */
    private function createValue($data)
    {
        return "{$data['token']}-{$data['uid']}-{$data['userAgent']}-{$data['ip']}-{$data['expire']}";
    }

    /**
     * @desc 生成token
     * @param $data
     * @return string
     */
    private function createPassword($data)
    {
        return $data['uid'] . $data['userAgent'] . $data['ip'] . $data['expire'];
    }

    /**
     * @desc 删除cookie
     * @return bool
     */
    public function delete()
    {
        return setcookie($this->name, null, time() - 100, $this->path, $this->domain);
    }

    /**
     * @return Login|self
     */
    public static function getInstance()
    {
        static $obj = null;
        return empty($obj) ? ($obj = new self()) : $obj;
    }
}