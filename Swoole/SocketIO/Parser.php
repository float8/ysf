<?php
namespace Swoole\SocketIO;
class Parser
{
    public static $packets = [
        'open' => 0,
        'close' => 1,
        'ping' => 2,
        'pong' => 3,
        'message' => 4,
        'upgrade' => 5,
        'noop' => 6
    ];

    public static function encodePacket($packet)
    {
        $data = !isset($packet['data']) ? '' : $packet['data'];
        return self::$packets[$packet['type']] . $data;
    }


    /**
     * Encodes a packet with binary data in a base64 string
     *
     * @param {Object} packet, has `type` and `data`
     * @return {String} base64 encoded message
     */

    public static function encodeBase64Packet($packet)
    {
        $packet['data'] = isset($packet['data']) ? '' : $packet['data'];
        return $message = 'b' . self::$packets[$packet['type']] . base64_encode($packet['data']);
    }

    /**
     * Encodes multiple messages (payload).
     *
     *     <length>:data
     *
     * Example:
     *
     *     11:hello world2:hi
     *
     * If any contents are binary, they will be encoded as base64 strings. Base64
     * encoded strings are marked with a b before the length specifier
     *
     * @param {Array} packets
     * @api private
     */

    public static function encodePayload($packets, $supportsBinary = null)
    {
        if ($supportsBinary) {
            return self::encodePayloadAsBinary($packets);
        }

        if (!$packets) {
            return '0:';
        }

        $results = '';
        foreach ($packets as $msg) {
            $results .= self::encodeOne($msg);
        }
        return $results;
    }


    public static function encodeOne($packet)
    {
        $message = self::encodePacket($packet);
        return strlen($message) . ':' . $message;
    }


    /**
     * Encodes multiple messages (payload) as binary.
     *
     * <1 = binary, 0 = string><number from 0-9><number from 0-9>[...]<number
     * 255><data>
     *
     * Example:
     * 1 3 255 1 2 3, if the binary contents are interpreted as 8 bit integers
     *
     * @param {Array} packets
     * @return {Buffer} encoded payload
     * @api private
     */

    public static function encodePayloadAsBinary($packets)
    {
        $results = '';
        foreach ($packets as $msg) {
            $results .= self::encodeOneAsBinary($msg);
        }
        return $results;
    }

    public static function encodeOneAsBinary($p)
    {
        $packet = self::encodePacket($p);
        $encodingLength = '' . strlen($packet);
        $sizeBuffer = chr(0);
        for ($i = 0; $i < strlen($encodingLength); $i++) {
            $sizeBuffer .= chr($encodingLength[$i]);
        }
        $sizeBuffer .= chr(255);
        return $sizeBuffer . $packet;
    }

}
