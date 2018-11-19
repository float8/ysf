<?php
/**
 * @desc:
 * @author: wanghongfeng
 * @date: 2018/11/8
 * @time: 下午8:10
 */

namespace RealTime\Base;


abstract class WebAction extends WebController
{
    public $controller;

    /**
     * @desc 获取 Controller
     * @return $this
     */
    public function getController()
    {
        return $this->controller;
    }

    abstract public function execute();

}