<?php
/**
 * @desc:
 * @author: wanghongfeng
 * @date: 2018/7/25
 * @time: 下午5:52
 */

namespace Core\Platform\Weixin\MiniProgram;


trait TplMsg
{
    /**
     * 模板库标题列表 Uri
     * @var string
     */
    private $libTplListUri = '/cgi-bin/wxopen/template/library/list';

    /**
     * @desc 获取小程序模板库标题列表
     * @param int $offset 表示从offset开始,offset从0开始
     * @param int $count 取count条记录，count最大为20。
     * @return mixed
     */
    public function getLibTplList($offset = 0, $count = 20){
        $query = [
            'offset'=>$offset,
            'count'=>$count
        ];
        return $this->post($this->libTplListUri, json_encode($query));
    }

    /**
     * 获取模板库某个模板标题下关键词库 Uri
     * @var string
     */
    private $libTplUri = '/cgi-bin/wxopen/template/library/get';

    /**
     * 获取模板库某个模板标题下关键词库
     * @param string $id 模板标题id，可通过接口获取，也可登录小程序后台查看获取
     * @return mixed
     */
    public function getLibTpl($id){
        return $this->post($this->libTplUri, json_encode(['id'=>$id]));
    }

    /**
     * 组合模板并添加至帐号下的个人模板库
     * @var string
     */
    private $addTplUri = '/cgi-bin/wxopen/template/add';

    /**
     * 组合模板并添加至帐号下的个人模板库
     * @param $id
     * @param $keyword_id_list
     * @return mixed
     */
    public function addTpl($id, $keyword_id_list){
        $query = [
            'id'=>$id,
            'keyword_id_list'=>$keyword_id_list
        ];
        return $this->post($this->addTplUri, json_encode($query));
    }

    /**
     * 获取帐号下已存在的模板列表 Uri
     * @var string
     */
    private $tplListUri = '/cgi-bin/wxopen/template/list';

    /**
     * 获取帐号下已存在的模板列表
     * @param int $offset
     * @param int $count
     * @return mixed
     */
    public function getTplList($offset = 0, $count = 20){
        $query = [
            'offset'=>$offset,
            'count'=>$count
        ];
        return $this->post($this->tplListUri, json_encode($query));
    }

    /**
     * 删除帐号下的某个模板 Uri
     * @var string
     */
    private $delTplUri = '/cgi-bin/wxopen/template/list';

    /**
     * 删除帐号下的某个模板
     * @param $template_id
     * @return mixed
     */
    public function delTpl($template_id){
        $query = [
            'template_id'=>$template_id,
        ];
        return $this->post($this->delTplUri, json_encode($query));
    }

    /**
     * 发送模版消息 Uri
     * @var string
     */
    private $sendTplMsgUri = '/cgi-bin/message/wxopen/template/send';

    /**
     * 发送模版消息
     * @param $data
     * @return mixed
     */
    public function sendTplMsg($data){
        return $this->post($this->sendTplMsgUri, json_encode($data));
    }
}