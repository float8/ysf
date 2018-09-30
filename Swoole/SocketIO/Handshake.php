<?php
/**
 * @desc: SocketIO 握手
 * @author: wanghongfeng
 * @date: 2017/10/30
 * @time: 下午5:41
 */
namespace Swoole\SocketIO;

use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\WebSocket\Server;

class Handshake
{
    /**
     * @desc transport=polling 握手
     * @param Request $request
     * @param Response $response
     */
    public static function polling(Request $request, Response $response){
        if($request->get['transport'] == 'polling' ) {
            $packet = !isset($request->get['sid']) ? self::first() : self::second();//获取握手的数据包
            self::write([$packet], $request, $response);//信息返回
        }
    }

    /**
     * @desc 第一次握手
     * @return array
     */
    private static function first(){
        $data = [
            'sid'=>self::getSid(),
            'upgrades'=>['websocket'],
            'pingInterval'=>25*1000,
            'pingTimeout'=>60*1000
        ];
        $packet = [
            'type' => 'open',
            'data' =>  json_encode($data)
        ];
        return $packet;
    }

    /**
     * @desc 第二次握手
     * @return array
     */
    private static function second(){
        return ['type'=>'message','data'=>Websocket\Parser\Parser::CONNECT];
    }

    /**
     * @desc 获取sid
     * @return string
     */
    private static function getSid(){
        return bin2hex(pack('d', microtime(true)) . pack('N', function_exists('random_int') ? random_int(1, 100000000) : rand(1, 100000000)));
    }


    /**
     * @desc 返回信息
     * @param $packets
     * @param Request $request
     * @param Response $response
     */
    private static function write($packets, Request $request, Response $response){
        $data = Parser::encodePayload($packets, 1);
        $origin = isset($request->header['origin']) ? $request->header['origin'] : '*';
        $response->header('Access-Control-Allow-Credentials', 'true');
        $response->header('Access-Control-Allow-Origin', $origin);
        $response->header('Content-Type','application/octet-stream');
        $response->header('Content-Length', strlen($data));
        $response->header('X-XSS-Protection','0');
        $response->write($data);
    }


    /**
     * @desc websocket握手
     * @param $fd
     * @param Server $server
     * @param Request $request
     * @return bool
     */
    public static function websocket($fd, Server $server, Request $request = null){
        if(empty($request)){
            $packet = Parser::encodePacket(['type'=>'pong','data'=>'probe']);
            return $server->push($fd, $packet);
        }
        if(isset($request->get['sid'])){
            return true;
        }
        $server->push($fd, Parser::encodePacket(self::first()));
        return $server->push($fd, Parser::encodePacket(['type'=>'message','data'=>Websocket\Parser\Parser::CONNECT]));
    }

}