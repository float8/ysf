<?php
namespace Swoole\SocketIO\Websocket\Parser;

/**
 * @desc 解码
 * Class Decoder
 * @package Swoole\SocketIO\Websocket\Parser
 */
class Decoder
{
    /**
     * @desc 解码字符串
     * @param $str
     * @return array
     */
    public static function decodeString($str)
    {
        $packets = \Swoole\SocketIO\Parser::$packets;
        if($str[0] == $packets['ping']){
            return ['type'=>'ping', 'data'=>substr($str, 1)];
        }echo $str[0],"\n";
        if($str[0] == $packets['message'] && $str[1] == Parser::EVENT){
            $packet = json_decode(substr($str, 2), true);
            $data = isset($packet[1]) ? $packet[1] : null;
            return ['type'=>strtolower($packet[0]), 'data'=>$data];
        }
        if($str[0] == $packets['upgrade']){
            return ['type'=>'upgrade', 'data'=>null];
        }
        return [];
    }

    /**
     * @desc 解包错误
     * @return array
     */
    public static function error()
    {
         return [
            'type'=> Parser::ERROR,
            'data'=> 'parser error'
         ];
    }
}
