<?php
/**
 * @desc:
 * @author: wanghongfeng
 * @date: 2018/10/23
 * @time: 下午9:20
 */

namespace RealTime\Base;


abstract class Event extends Controller
{
    abstract public function execute();
}