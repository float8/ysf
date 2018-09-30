<?php
/**
 * @desc: 小程序
 * @author: wanghongfeng
 * @date: 2018/7/25
 * @time: 下午6:11
 */

namespace Core\Platform\Weixin;

use Core\Platform\Weixin\MiniProgram\Core;
use Core\Platform\Weixin\MiniProgram\CustomMessage;
use Core\Platform\Weixin\MiniProgram\Datacube;
use Core\Platform\Weixin\MiniProgram\TplMsg;
use Exception;

class MiniProgram extends Base
{
    use Core, CustomMessage, Datacube, TplMsg;

    /**
     * @desc jscode2session uri
     * @var string
     */
    private $jscode2sessionUri = '/sns/jscode2session';

    /**
     * @desc 登录凭证校验
     * @param $js_code
     * @return mixed
     * @throws Exception
     */
    public function getJscode2session($js_code)
    {
        if(empty($js_code)){
            throw new Exception('invalid code', 40029);
        }
        $query = [
            'appid'=>$this->getAppid(),
            'secret'=>$this->getSecret(),
            'js_code'=>$js_code,
            'grant_type'=>'authorization_code'
        ];
        return $this->get($this->jscode2sessionUri, $query, false);
    }

    /**
     * @desc 实例化子类
     * @return $this
     */
    public static function getInstance()
    {
        static $obj = null;
        return empty($obj) ? ($obj = new static()) : $obj;
    }
}