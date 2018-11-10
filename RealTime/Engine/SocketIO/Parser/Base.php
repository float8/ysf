<?php
/**
 * @desc:
 * @author: wanghongfeng
 * @date: 2018/11/1
 * @time: 下午5:13
 */

namespace RealTime\Engine\SocketIO\Parser;


abstract class Base
{
    abstract public function encode($packet);
    abstract public function decode($packet);
}