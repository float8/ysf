<?php
/**
 * @desc:
 * @author: wanghongfeng
 * @date: 2018/11/1
 * @time: 下午4:39
 */

namespace RealTime\Engine\SocketIO\Parser;


use Core\Base\Exception;

class SocketIO extends Base
{
    /**
     * @desc 数据包类型
     * @var array
     */
    public $types = ['CONNECT', 'DISCONNECT', 'EVENT', 'ACK', 'ERROR', 'BINARY_EVENT', 'BINARY_ACK'];

    /**
     * Packet type `connect`.
     *
     * @api public
     */
    const CONNECT = 0;

    /**
     * Packet type `disconnect`.
     *
     * @api public
     */
    const DISCONNECT = 1;

    /**
     * Packet type `event`.
     *
     * @api public
     */
    const EVENT = 2;

    /**
     * Packet type `ack`.
     *
     * @api public
     */
    const ACK = 3;

    /**
     * Packet type `error`.
     *
     * @api public
     */
    const ERROR = 4;

    /**
     * Packet type 'binary event'
     *
     * @api public
     */
    const BINARY_EVENT = 5;

    /**
     * Packet type `binary ack`. For acks with binary arguments.
     *
     * @api public
     */
    const BINARY_ACK = 6;

    private $errorPacket = self::ERROR. '"encode error"';

    public function decode($packet)
    {
        if(is_string($packet)){
            $packet = $this->decodeString($packet);
            if(self::BINARY_EVENT == $packet['type'] || self::BINARY_ACK === $packet['type']){
//                this.reconstructor = new BinaryReconstructor(packet);
//
//                // no attachments, labeled binary but no binary data to follow
//                if (this.reconstructor.reconPack.attachments === 0) {
//                    this.emit('decoded', packet);
//                }
            }
            return $packet;
        }


//            var packet;
//            if (typeof obj === 'string') {
//            packet = decodeString(obj);
//            if (exports.BINARY_EVENT === packet.type || exports.BINARY_ACK === packet.type) { // binary packet's json
//                this.reconstructor = new BinaryReconstructor(packet);
//
//                // no attachments, labeled binary but no binary data to follow
//                if (this.reconstructor.reconPack.attachments === 0) {
//                    this.emit('decoded', packet);
//                }
//            } else { // non-binary full packet
//                this.emit('decoded', packet);
//            }
//        }
//          else if (isBuf(obj) || obj.base64) { // raw binary data
//            if (!this.reconstructor) {
//                throw new Error('got binary data when not reconstructing a packet');
//            } else {
//                packet = this.reconstructor.takeBinaryData(obj);
//                if (packet) { // received final buffer
//                    this.reconstructor = null;
//                    this.emit('decoded', packet);
//                }
//            }
//        }
//        else {
//            throw new Error('Unknown type: ' + obj);
//        }

    }

    /**
     * @desc 编码
     * @param $packet
     * @return string
     */
    public function encode($packet)
    {
        if (self::BINARY_EVENT === $packet['type'] || self::BINARY_ACK === $packet['type']) {
            //encodeAsBinary(obj, callback);
            $packet;
        } else {
            $packet = $this->encodeAsString($packet);
        }
        return $packet;
    }

    /**
     * @desc 错误
     * @param $msg
     * @return array
     */
    private function error($msg)
    {
        return ['type' => self::ERROR, 'data' => 'parser error: ' . $msg];
    }

    /**
     * @desc 解码字符串
     * @param $str
     */
    private function decodeString($str)
    {
        $i = 0;
        // look up type
        $packet = ['type'=>intval($str[0])];
        // The type does not exist
        if (!isset($this->types[$packet['type']])) {
            return $this->error('unknown packet type ' .$packet['type']);
        }
        // look up attachments if type binary
        if(self::BINARY_EVENT == $packet['type'] || self::BINARY_ACK == $packet['type']) {
            $buf = '';
            while ($str[++$i] !== '-') {
                $buf .= $str[$i];
                if ($i == strlen($str)) break;
            }
            if (strcmp($buf, intval($buf)) != 0 || $str[$i] !== '-') {
                throw new Exception('Illegal attachments');
            }
            $packet['attachments'] = $buf;
        }

	    // look up namespace (if any)
        $packet['nsp'] = '/';
        if ('/' === $str[$i+1]) {
            $packet['nsp'] = '';
            while (++$i) {
                $c = $str[$i];
                if (',' === $c) break;
                $packet['nsp'] .= $c;
                if ($i === strlen($str)) break;
            }
        }

	    // look up id
        if (isset($str[$i+1]) && strcmp($str[$i+1], intval($str[$i+1])) == 0) {
            $packet['id'] = '';
            while (++$i) {
                $c = $str[$i];
                if (null == $c || strcmp($c, intval($c)) != 0) {
                    --$i;
                    break;
                }
                $packet['id'] .= $str[$i];
                if ($i === strlen($str)) break;
            }
            $packet['id'] = intval($packet['id']);
        }

        // look up json data
        if (isset($str[++$i])) {
            $payload = json_decode(substr($str, $i), true);
            $isPayloadValid = $payload !== false && ($packet['type'] === self::ERROR || is_array($payload));
            if ($isPayloadValid) {
                $packet['data'] = $payload;
            } else {
                return $this->error('invalid payload');
            }
        }
        return $packet;
    }

    /**
     * Decode a packet String (JSON data)
     *
     * @param {String} str
     * @return {Object} packet
     * @api private
     */

//    function decodeString(str) {
//        var i = 0;
//        // look up type
//        var p = {
//            type: Number(str.charAt(0))
//	  };
//
//	  if (null == exports.types[p.type]) {
//          return error('unknown packet type ' + p.type);
//      }
//
//	  // look up attachments if type binary
//	  if (exports.BINARY_EVENT === p.type || exports.BINARY_ACK === p.type) {
//          var buf = '';
//          while (str.charAt(++i) !== '-') {
//              buf += str.charAt(i);
//              if (i == str.length) break;
//          }
//          if (buf != Number(buf) || str.charAt(i) !== '-') {
//              throw new Error('Illegal attachments');
//          }
//          p.attachments = Number(buf);
//      }
//
//	  // look up namespace (if any)
//	  if ('/' === str.charAt(i + 1)) {
//          p.nsp = '';
//          while (++i) {
//              var c = str.charAt(i);
//              if (',' === c) break;
//              p.nsp += c;
//              if (i === str.length) break;
//          }
//      } else {
//          p.nsp = '/';
//      }
//
//	  // look up id
//	  var next = str.charAt(i + 1);
//	  if ('' !== next && Number(next) == next) {
//          p.id = '';
//          while (++i) {
//              var c = str.charAt(i);
//              if (null == c || Number(c) != c) {
//                  --i;
//                  break;
//              }
//              p.id += str.charAt(i);
//              if (i === str.length) break;
//          }
//          p.id = Number(p.id);
//      }
//
//	  // look up json data
//	  if (str.charAt(++i)) {
//          var payload = tryParse(str.substr(i));
//          var isPayloadValid = payload !== false && (p.type === exports.ERROR || isArray(payload));
//          if (isPayloadValid) {
//              p.data = payload;
//          } else {
//              return error('invalid payload');
//          }
//      }
//
//	  debug('decoded %s as %j', str, p);
//	  return p;
//	}

    /**
     * @desc 编码字符串
     * @param $packet
     * @return string
     */
    private function encodeAsString($packet)
    {
        // first is type
        $str = '' . $packet['type'];

        // attachments if we have them
        if (self::BINARY_EVENT === $packet['type'] || self::BINARY_ACK === $packet['type']) {
            $str .= $packet['attachments'] . '-';
        }

        // if we have a namespace other than `/`
        // we append it followed by a comma `,`
        if ($packet['nsp'] && '/' !== $packet['nsp']) {
            $str .= $packet['nsp'] . ',';
        }

        // immediately followed by the id
        if ( isset($packet['id']) && is_numeric($packet['id']) ) {
            $str .= $packet['id'];
        }
        // json data
        if (isset($packet['data'])) {
            $payload = json_encode($packet['data']);
            if ($payload !== false) {
                $str .= $payload;
            } else {
                return $this->errorPacket;
            }
        }
        return $str;
    }

    /**
     * Encode packet as 'buffer sequence' by removing blobs, and
     * deconstructing packet into object with placeholders and
     * a list of buffers.
     *
     * @param {Object} packet
     * @return {Buffer} encoded
     * @api private
     */

/*    private function encodeAsBinary(obj, callback) {

        function writeEncoding(bloblessData) {
            var deconstruction = binary.deconstructPacket(bloblessData);
            var pack = encodeAsString(deconstruction.packet);
            var buffers = deconstruction.buffers;

            buffers.unshift(pack); // add packet info to beginning of data list
            callback(buffers); // write all the buffers
        }

        binary.removeBlobs(obj, writeEncoding);
    }*/




}