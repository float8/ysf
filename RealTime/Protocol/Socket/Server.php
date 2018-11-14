<?php
/**
 * @desc:
 * @author: wanghongfeng
 * @date: 2018/10/25
 * @time: 上午11:08
 */

namespace RealTime\Protocol\Socket;


class Server
{
    private $event = [
        //Master进程内的回调函数
        'Start',
        'Shutdown',
        'MasterConnect',
        'MasterClose',
        'Timer',
        //Worker进程内的回调函数
        'WorkerStart',
        'WorkerStop',
        'WorkerExit',
        'WorkerError',
        'Connect',
        'Receive',
        'Close',
        'Timer',
        'Finish',
        'Packet',
        'BufferFull',
        'BufferEmpty',
        'PipeMessage',
        //task_worker进程内的回调函数
        'Task',
        //Manager进程内的回调函数
        'ManagerStart',
        'ManagerStop'
    ];

}