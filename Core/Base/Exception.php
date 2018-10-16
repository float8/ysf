<?php
/**
 * @desc:异常处理
 * @author: wanghongfeng
 * @date: 2017/6/26
 * @time: 上午2:05
 */

namespace Core\Base;
/**
 * Class Exception
 * @package Core\Base
 */
class Exception extends \Exception
{
    public function __construct($message, $code = 0)
    {
        parent::__construct($message, $code);
        Log::write(LOG_INFO, $this->getMessage(), ['line'=>$this->getLine(), 'file'=>$this->getFile()]);//记录日志
    }
}