<?php
namespace Our\Tools;

use Core\Base\Config;
use Our\Tools\PHPMailer\PHPMailer;

/**
 * @desc 发送邮件
 * Class SendMailModel
 * @package Our\Tools
 */
class SendMailModel {
	
	private $mail = null;
	
	public function __construct(){
		$this->mail = new PHPMailer(); //建立邮件发送类
		$this->mail->IsSMTP(); // 使用SMTP方式发送

		$this->mail->Host = Config::app('yaf.email.host'); // 您的企业邮局域名
		$this->mail->SMTPAuth = Config::app('yaf.email.SMTPAuth'); // 启用SMTP验证功能
		$this->mail->Username = Config::app('yaf.email.username'); // 邮局用户名(请填写完整的email地址)
		$this->mail->Password = Config::app('yaf.email.password'); // 邮局密码
		$this->mail->Port= Config::app('yaf.email.port');//端口
		$this->mail->From = Config::app('yaf.email.from');//邮件发送者email地址
		$this->mail->CharSet = Config::app('yaf.email.charSet');//编码
		$this->mail->FromName = Config::app('yaf.email.fromName');//form

		$this->mail->IsHTML(Config::app('yaf.email.isHTML')); // set email format to HTML //是否使用HTML格式
	}
	
	/**
	 * @desc 发送
	 * @param string $address
	 * @param string $subject
	 * @param string $body
	 * @return boolean
	 */
	public function send($address, $subject, $body) {
		$this->mail->AddAddress($address);//收件人地址，可以替换成任何想要接收邮件的email信箱,格式是AddAddress("收件人email","收件人姓名")
		$this->mail->Subject = $subject; //邮件标题
		$this->mail->Body = $body; //邮件内容
		return $this->mail->Send();
	}
	/**
	 * @desc 获取错误信息
	 * @return string
	 */
	public function getErrorInfo() {
		return $this->mail->ErrorInfo;
	}
	
}