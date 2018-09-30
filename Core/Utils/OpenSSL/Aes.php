<?php
/**
 * @desc:
 * @author: wanghongfeng
 * @date: 2017/3/23
 * @time: 下午2:18
 */
namespace Core\Utils\OpenSSL;
/**
 * Class Aes
 * @package Core\Utils\OpenSSL
 */
class Aes
{
    /**
     * @var int AES长度
     */
    private $size = 256;
    /**
     * @var string 加密模式
     */
    private $mode = 'CBC';
    /**
     * @var string 加密key
     */
    private $key = '';
    /**
     * @var string 加密向量
     */
    private $iv = null;

    /**
     * @desc 加密
     * @param string $data
     * @return string
     */
    public function encrypt(string $data): string
    {
        return openssl_encrypt($data, self::getMethod(), $this->key, OPENSSL_RAW_DATA, $this->iv);
    }

    /**
     * @desc 解密
     * @param string $data
     * @return string
     */
    public function decrypt(string $data): string
    {
        return openssl_decrypt($data, self::getMethod(), $this->key, OPENSSL_RAW_DATA, $this->iv);
    }

    /**
     * @desc 获取方法
     * @return string
     */
    private function getMethod()
    {
        return "AES_{$this->mode}_{$this->size}";
    }

    /**
     * @desc 获取加密串长度
     * @return int
     */
    public function getSize(): int
    {
        return $this->size;
    }

    /**
     * @desc 设置AES长度为128
     */
    public function setSize128()
    {
        $this->size = 128;
    }

    /**
     * @desc 设置AES长度为192
     */
    public function setSize192()
    {
        $this->size = 192;
    }

    /**
     * @desc 设置AES长度为256
     */
    public function setSize256()
    {
        $this->size = 256;
    }

    /**
     * @desc 获取加密方式
     * @return string
     */
    public function getMode(): string
    {
        return $this->mode;
    }

    /**
     * @desc 设置模式为 CBC
     */
    public function setModeCBC()
    {
        $this->mode = 'CBC';
    }

    /**
     * @desc 设置模式为 CFB
     */
    public function setModeCFB()
    {
        $this->mode = 'CFB';
    }

    /**
     * @desc 设置模式为 CFB1
     */
    public function setModeCFB1()
    {
        $this->mode = 'CFB1';
    }

    /**
     * @desc 设置模式为 CFB8
     */
    public function setModeCFB8()
    {
        $this->mode = 'CFB8';
    }

    /**
     * @desc 设置模式为 ECB
     */
    public function setModeECB()
    {
        $this->mode = 'ECB';
    }

    /**
     * @desc 设置模式为 OFB
     */
    public function setModeOFB()
    {
        $this->mode = 'OFB';
    }

    /**
     * @desc 获得加密key
     * @return string
     */
    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * @desc 设置加密key
     * @param string $key
     */
    public function setKey(string $key)
    {
        $this->key = $key;
    }

    /**
     * @desc 获取向量
     * @return string
     */
    public function getIv(): string
    {
        return $this->iv;
    }

    /**
     * @desc 设置向量
     * @param string $iv
     */
    public function setIv(string $iv)
    {
        $this->iv = $iv;
    }
}