<?php
/**
 * @desc:
 * @author: wanghongfeng
 * @date: 2017/6/21
 * @time: 下午1:54
 */

namespace Core\Utils\Tools;
use Core\Utils\Tools\Weixin\Core;
use Core\Utils\Tools\Weixin\JsApi;
use Core\Utils\Tools\Weixin\OAuth;

class Weixin
{
    use OAuth,Core,JsApi;

    /**
     * @desc 二维码创建
     * @var string
     */
    private $qrcodeCreateUri = '/cgi-bin/qrcode/create';

    /**
     * @desc 创建二维码
     * @param $data
     * @return mixed
     */
    public function qrcodeCreate($data) {
        $result = $this->post($this->qrcodeCreateUri, json_encode($data));
        $result = $this->jsonDecode($result,'weixin:qrcodeCreate:fail-');
        return $result;
    }


    /**
     * @var string
     */
    private $showqrcodeUri = '/cgi-bin/showqrcode';
    /**
     * @desc 通过ticket换取二维码
     * @param $ticket
     * @return string
     */
    public function showqrcode($ticket) {
        return $this->getUrl($this->showqrcodeUri, ['ticket'=>urlencode($ticket)], 'mp');
    }


    /**
     * @desc 获取永久素材列表URi
     * @var string
     */
    private $batchgetMaterialUri = '/cgi-bin/material/batchget_material';

    /**
     * @desc 获取素材列表
     * @param $data
     * @return mixed
     */
    public function batchgetMaterial($data){
        $result = $this->post($this->batchgetMaterialUri, json_encode($data));
        $result = $this->jsonDecode($result,'weixin:batchgetMaterial:fail-');
        return $result;
    }


    /**
     * @var string
     */
    private $messageUri = '/cgi-bin/message/template/send';

    /**
     * @desc 通过微信发送模板消息
     * @param $data
     * @return mixed
     */
    public function msgTplSend($data) {
        $result = $this->post($this->messageUri, json_encode($data));
        $result = $this->jsonDecode($result,'weixin:msgTplSend:fail-');
        return $result;
    }

    /**
     * @desc 微信创建菜单接口
     * @var string
     */
    private $menuCreateUri = '/cgi-bin/menu/create';

    /**
     * @desc 创建微信自定义菜单
     * @param $data
     * @return mixed
     */
    public function menuCreate($data) {
        $result = $this->post($this->menuCreateUri, json_encode($data,JSON_UNESCAPED_UNICODE));
        $result = $this->jsonDecode($result,'weixin:menuCreate:fail-');
        return $result;
    }

    /**
     * @desc 微信查询菜单接口
     * @var string
     */
    private $menuGetUri = '/cgi-bin/menu/get';

    /**
     * desc 创建微信自定义菜单
     * @return mixed
     */
    public function menuGet() {
        $result = $this->get($this->menuGetUri);
        $result = $this->jsonDecode($result,'weixin:menuGet:fail-');
        return $result;
    }

    /**
     * @return static
     */
    public static function getInstance() {
        static $obj = null;
        return empty($obj) ? ($obj = new static()) : $obj;
    }

}