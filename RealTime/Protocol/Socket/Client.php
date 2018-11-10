<?php
/**
 * @desc:
 * @author: wanghongfeng
 * @date: 2018/10/25
 * @time: 上午11:08
 */

namespace RealTime\Protocol\Socket;


class Client
{
    private $event = [
        'Connect',
        'Error',
        'Receive',
        'Close',
        'BufferFull',
        'BufferEmpty',
    ];
}