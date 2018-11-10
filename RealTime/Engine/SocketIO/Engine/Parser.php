<?php
/**
 * @desc:
 * @author: wanghongfeng
 * @date: 2018/10/31
 * @time: 下午2:40
 */

namespace RealTime\Engine\SocketIO\Engine;

use Core\Utils\Tools\Fun;

trait Parser
{
    /**
     * @desc 数据包类型
     * @var array
     */
    private $types = [
        'open' => 0,
        'close' => 1,
        'ping' => 2,
        'pong' => 3,
        'message' => 4,
        'upgrade' => 5,
        'noop' => 6,
    ];

    /**
     *  数据包类型的反转
     * @var array
     */
    private $typesReverse = [];

    /**
     * @desc 错误包信息
     * @var array
     */
    private $err = ['type' => 'error', 'data' => 'parser error'];

    /**
     * @desc 编码
     * @param $packet
     * @return mixed
     */
    private function encodePacket($packet, $supportsBinary = null, $utf8encode = null)
    {


//
//
//        if (typeof supportsBinary === 'function') {
//        callback = supportsBinary;
//        supportsBinary = false;
//    }
//	  if (typeof utf8encode === 'function') {
//        callback = utf8encode;
//        utf8encode = null;
//    }
//	  var data = (packet.data === undefined)
//        ? undefined
//        : packet.data.buffer || packet.data;
//	  if (global.ArrayBuffer && data instanceof ArrayBuffer) {
//        return encodeArrayBuffer(packet, supportsBinary, callback);
//    } else if (Blob && data instanceof global.Blob) {
//        return encodeBlob(packet, supportsBinary, callback);
//    }
//
//	  // might be an object with { base64: true, data: dataAsBase64String }
//	  if (data && data.base64) {
//          return encodeBase64Object(packet, callback);
//      }

        $type = Fun::get($packet, 'type');
        $data = Fun::get($packet, 'data');
        // Sending data as a utf-8 string
        $encoded = $this->types[$type];
        // data fragment is optional
        if ($data !== null) {
            $encoded .= $utf8encode ? utf8_encode(strval($data)) : strval($data);
        }
        return $encoded;
    }


    /**
     * @desc 解码
     * @param $data
     * @return mixed
     */
    private function decodePacket($data, $supportsBinary = null, $utf8encode = null)
    {
        if (empty($data)) {
            return $this->err;
        }

        // binary data
        if(is_string($data) && $data[0] === 'b') {
            //            return exports . decodeBase64Packet(data . substr(1), binaryType);
            return ;
        }
//        print_r($this->typesReverse);
        // String data error
        if(is_string($data) && (!is_numeric($data[0]) || !isset($this->typesReverse[$data[0]]))){
            return $this->err;
        }

        // String data
        if(is_string($data)){
            return strlen($data) == 1 ?
                ['type'=>$this->typesReverse[$data[0]]] :
                ['type'=>$this->typesReverse[$data[0]], 'data'=> substr($data, 1)];
        }

//          var asArray = new Uint8Array(data);
//          var type = asArray[0];
//          var rest = sliceBuffer(data, 1);
//          if (Blob && binaryType === 'blob') {
//              rest = new Blob([rest]);
//          }
//          return {
//        type:
//        packetslist[type], data: rest };
    }



    /**
     * Decodes a packet. Changes format to Blob if requested.
     *
     * @return {Object} with `type` and `data` (if any)
     * @api private
     */

//exports.decodePacket = function (data, binaryType, utf8decode) {
//    if (data === undefined) {
//        return err;
//    }
//    // String data
//    if (typeof data === 'string') {
//        if (data.charAt(0) === 'b') {
//            return exports.decodeBase64Packet(data.substr(1), binaryType);
//        }
//
//        if (utf8decode) {
//            data = tryDecode(data);
//            if (data === false) {
//                return err;
//            }
//        }
//        var type = data.charAt(0);
//        if (Number(type) != type || !packetslist[type]) {
//            return err;
//        }
//
//        //console.log({ type: packetslist[type], data: data.substring(1) });
//        if (data.length > 1) {
//            return { type: packetslist[type], data: data.substring(1) };
//	    } else {
//            return { type: packetslist[type] };
//	    }
//    }
//
//	  var asArray = new Uint8Array(data);
//	  var type = asArray[0];
//	  var rest = sliceBuffer(data, 1);
//	  if (Blob && binaryType === 'blob') {
//          rest = new Blob([rest]);
//      }
//	  return { type: packetslist[type], data: rest };
//	};


//    function tryDecode(data) {
//        try {
//            data = utf8.decode(data, { strict: false });
//	  } catch (e) {
//            return false;
//        }
//        return data;
//    }

    /**
     * Handles a packet.
     *
     * @api private
     */

//Socket.prototype.onPacket = function (packet) {
//    if ('opening' === this.readyState || 'open' === this.readyState ||
//        'closing' === this.readyState) {
//        debug('socket receive: type "%s", data "%s"', packet.type, packet.data);
//
//        this.emit('packet', packet);
//
//        // Socket is live - any packet counts
//        this.emit('heartbeat');
//
//        switch (packet.type) {
//            case 'open':
//                this.onHandshake(JSON.parse(packet.data));
//                break;
//
//            case 'pong':
//                this.setPing();
//                this.emit('pong');
//                break;
//
//            case 'error':
//                var err = new Error('server error');
//                err.code = packet.data;
//                this.onError(err);
//                break;
//
//            case 'message':console.log(packet);
//                this.emit('data', packet.data);
//                this.emit('message', packet.data);
//                break;
//        }
//    } else {
//        debug('packet received with socket readyState "%s"', this.readyState);
//    }
//};

    /**
     * Called upon handshake completion.
     *
     * @param {Object} handshake obj
     * @api private
     */

//Socket.prototype.onHandshake = function (data) {
//    this.emit('handshake', data);
//    this.id = data.sid;
//    this.transport.query.sid = data.sid;
//    this.upgrades = this.filterUpgrades(data.upgrades);
//    this.pingInterval = data.pingInterval;
//    this.pingTimeout = data.pingTimeout;
//    this.onOpen();
//    // In case open handler closes socket
//    if ('closed' === this.readyState) return;
//    this.setPing();
//
//    // Prolong liveness of socket on heartbeat
//    this.removeListener('heartbeat', this.onHeartbeat);
//    this.on('heartbeat', this.onHeartbeat);
//};
}