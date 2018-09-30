<?php
/**
 * @desc: 客服消息
 * @author: wanghongfeng
 * @date: 2018/7/25
 * @time: 下午5:55
 */

namespace Core\Platform\Weixin\MiniProgram;
//Token r1qw4r3sv9nqpv0fc34tcwdiwp48vsfz
//EncodingAESKey eZ4dNIogGWOb8PXeWKqQaqo30uv8ttrMFPHGAHAWX3m
use Core\Base\Log;
use Core\Utils\Tools\Fun;
use Exception;

/**
 * PKCS7Encoder class
 *
 * 提供基于PKCS7算法的加解密接口.
 */
class PKCS7Encoder
{
    public static $block_size = 32;

    /**
     * 对需要加密的明文进行填充补位
     * @param $text 需要进行填充补位操作的明文
     * @return 补齐明文字符串
     */
    function encode($text)
    {
        $block_size = PKCS7Encoder::$block_size;
        $text_length = strlen($text);
        //计算需要填充的位数
        $amount_to_pad = PKCS7Encoder::$block_size - ($text_length % PKCS7Encoder::$block_size);
        if ($amount_to_pad == 0) {
            $amount_to_pad = PKCS7Encoder::$block_size;
        }
        //获得补位所用的字符
        $pad_chr = chr($amount_to_pad);
        $tmp = "";
        for ($index = 0; $index < $amount_to_pad; $index++) {
            $tmp .= $pad_chr;
        }
        return $text . $tmp;
    }

    /**
     * 对解密后的明文进行补位删除
     * @param decrypted 解密后的明文
     * @return 删除填充补位后的明文
     */
    function decode($text)
    {

        $pad = ord(substr($text, -1));
        if ($pad < 1 || $pad > 32) {
            $pad = 0;
        }
        return substr($text, 0, (strlen($text) - $pad));
    }

}
trait CustomMessage
{
    /**
     * @desc 加解密的token
     * @var string
     */
    private $cusromMsgtoken = '';

    /**
     * @desc 加解密的aes key
     * @var string
     */
    private $cusromMsgAesKey = '';

    private function msgSign($msg_encrypt)
    {
        $timestamp = Fun::get($_GET, 'timestamp');
        $nonce = Fun::get($_GET, 'nonce');
        $msg_signature = Fun::get($_GET, 'msg_signature');
        $signatureArr = [$this->getCusromMsgtoken(), $timestamp, $nonce, $msg_encrypt];
        sort($signatureArr);
        $dev_msg_signature = sha1(implode('', $signatureArr));
        if(strcmp($dev_msg_signature, $msg_signature)) {
            throw new Exception('签名验证错误',-40001);
        }
    }

    public function receiveMsg($dataFormat = 'json')
    {

        $signature = Fun::get($_GET, 'signature');
        $openid = Fun::get($_GET, 'openid');
        $encrypt_type = Fun::get($_GET, 'encrypt_type');

        $data = file_get_contents("php://input");//所有参数json格式
        if(empty($data)){
            throw new Exception();
        }

        Log::write(LOG_INFO, $data, 'weixin mini program msg');//添加日志
        $data = $dataFormat == 'json' ? json_decode($data) : simplexml_load_string($data, 'SimpleXMLElement', LIBXML_NOCDATA);
        if (!$data || !is_object($data)){
            throw new Exception();
        }
        try {
            $this->msgSign($data->Encrypt);
            $encrypt = base64_decode($data->Encrypt);
            $aesKey = base64_decode($this->getCusromMsgAesKey().'=');
            $iv = substr($aesKey, 0, 16);
            $result = openssl_decrypt($encrypt, "AES-128-CBC", $aesKey, OPENSSL_RAW_DATA, $iv);
            //去除补位字符
            $pkc_encoder = new PKCS7Encoder;
            $result = $pkc_encoder->decode($result);
//            var_dump($result);
//            var_dump(openssl_error_string());
            Log::write(LOG_INFO, var_export($result, true), 'weixin encrypt');//添加日志
        } catch (\Exception $e) {
            var_dump($e->getTraceAsString());
        }


//
//        Log::write(LOG_ERR, $result, 'weixin encrypt');//添加日志

//        {"signature":"359ec6dcad898d59b38063688193a31a8ff3ea46","timestamp":"1532761405","nonce":"1096166508","openid":"oJ1w341pCfy6dVq-bVjNRTtaSM14","encrypt_type":"aes","msg_signature":"dae9ee60790b5128da1341319c6474d809eb1ea7"}








        //$data->Encrypt
//        Log::write(LOG_INFO, json_encode($_GET), 'weixin mini program msg');//添加日志
    }

    /**
     * @return string
     */
    public function getCusromMsgtoken(): string
    {
        return $this->cusromMsgtoken;
    }

    /**
     * @param string $cusromMsgtoken
     */
    public function setCusromMsgtoken(string $cusromMsgtoken)
    {
        $this->cusromMsgtoken = $cusromMsgtoken;
    }

    /**
     * @return string
     */
    public function getCusromMsgAesKey(): string
    {
        return $this->cusromMsgAesKey;
    }

    /**
     * @param string $cusromMsgAesKey
     */
    public function setCusromMsgAesKey(string $cusromMsgAesKey)
    {
        $this->cusromMsgAesKey = $cusromMsgAesKey;
    }

}