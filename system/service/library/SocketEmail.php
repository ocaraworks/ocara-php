<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架   Socket邮件发送插件类SocketEmail
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara\Service;

use Ocara\Ocara;
use Ocara\ServiceBase;

defined('OC_PATH') or exit('Forbidden!');

final class SocketEmail extends ServiceBase
{
	public $lastResult;
	public $fo;
	public $sender;
	public $username;
	public $password;
	public $lastCommand;

	/**
	 * 析构函数
	 * @param string $sender
	 * @param string $host
	 * @param string $port
	 * @param string $username
	 * @param string $password
	 * @param integer $timeout
	 */
	public function __construct($sender, $host, $port, $username, $password, $timeout = 20)
	{
		$this->fo = @fsockopen(gethostbyname($host), $port, $errno, $errmsg, $timeout);
		
		if (empty($this->fo)) {
			$errmsg = iconv('gbk', 'utf-8', $errmsg);
            ocService()->error->show('failed_email_socket_connect', array($errno, $errmsg));
		}
		
		if ($timeout) {
			@socket_set_timeout($this->fo, $timeout);
		}
		
		$this->sender   = $sender;
		$this->username = $username;
		$this->password = $password;
	}

	/**
	 * 发送邮件
	 * @param string $receiver
	 * @param string $header
	 * @param string $content
	 */
	public function send($receiver, $header, $content)
	{
		@socket_set_blocking($this->fo, 1);
		
		$this->lastResult = fgets($this->fo, 512);
		if (!preg_match('/^220/', $this->lastResult)) {
			return false;
		}

		if (!$this->putCmd("HELO 127.0.0.1", 250)) {
			return false;
		}
		if (!$this->putCmd("AUTH LOGIN ". base64_encode($this->username), 334)) {
			return false;
		}
		if (!$this->putCmd(base64_encode($this->password), 235)) {
			return false;
		}
		if (!$this->putCmd("MAIL FROM:<{$this->sender}>", 250)) {
			return false;
		}
		if (!$this->putCmd("RCPT TO:<{$receiver}>", 250)) {
			return false;
		}
		if (!$this->putCmd("DATA", 354)) {
			return false;
		}
   
        $content = $content."\r\n.\r\n"; 
		fputs($this->fo, $header."\r\n" . $content);

		if (!$this->putCmd("QUIT", 250)) {
			return false;
		}
		
		fclose($this->fo); 
		return true;
	}

	/**
	 * 执行命令
	 * @param string $command
	 * @param integer $errorStatus
	 */
	public function putCmd($command, $errorStatus)
	{
		$command = $command . "\r\n";
		$this->lastCommand = $command;
		
		@fputs($this->fo, $command);
		$this->lastResult = @fgets($this->fo, 1024);

		if (preg_match('/^' . $errorStatus . OC_DIR_SEP, $this->lastResult)) {
			return true;
		}
		
		@fclose($this->fo);
		return false;
	}
}

?>