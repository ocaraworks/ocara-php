<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架    Email发送服务插件Mail
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara\Service;
use Ocara\ServiceBase;

class Mail extends ServiceBase
{
	/**
	 * @var $boundary 边界字符串
	 */
	public $sender;
	public $receiver;
	public $host;
	public $port;
	
	private $_username;
	private $_password;
	
	public $cc;
	public $bcc;
	public $subject;
	public $content;
	public $header;
	
	public $text;
	public $html;
	public $related;
	public $attachments;
	public $params;
	public $boundary;

	/**
	 * 析构函数
	 */
	public function __construct()
	{
		$sender 	= ocConfig('EMAIL.sender');
		$host 		= ocConfig('EMAIL.host');
		$port 		= ocConfig('EMAIL.port');
		$username 	= ocConfig('EMAIL.username');
		$password 	= ocConfig('EMAIL.password');
		
		$this->setServer($host, $port, $username, $password);
		$this->setSender($sender);
		
		$this->boundary = $this->newBoundary(1);
	}

	/**
	 * 设置SMTP服务器信息
	 * @param string $host
	 * @param string $port
	 * @param string $username
	 * @param string $password
	 */
	public function setServer($host, $port, $username = false, $password = false)
	{
		$this->host 	= $host;
		$this->port 	= $port;
		$this->_username = $username;
		$this->_password = $password;
	}
	
	/**
	 * 设置发件人
	 * @param string $sender
	 */
	public function setSender($sender)
	{
		$this->sender 	= $sender;
		$this->replyTo  = $sender;
	}
	
	/**
	 * 设置收件人和抄送人
	 * @param string $receiver
	 * @param array $cc
	 * @param array $bcc
	 */
	public function setReceiver($receiver, $cc = false, $bcc = false)
	{
		$this->cc 		= $this->packCc(ocForceArray($cc));
		$this->bcc 		= $this->packBcc(ocForceArray($bcc));
		$this->receiver = $receiver;
	}

	/**
	 * 设置标题
	 * @param string $subject
	 */
	public function setSubject($subject)
	{
		$this->subject = $subject;
	}
	
	/**
	 * 本机smtp方式发送邮件
	 * @param string $header
	 * @param array $params
	 */
	public function localSend($header = false, $params = false)
	{
		if ($this->host) @ini_set('SMTP', $this->host);
		if ($this->port) @ini_set('smtp_port', $this->port);
		
		$this->params = $params;
		
		$contentType = 'multipart/alternative';
		
		if ($this->related || $this->attachments) {
			$contentType = 'multipart/mixed';
		}
		
		$this->header 	= $this->getHeader($contentType);
		$this->content 	= $this->getContent();

		return mail($this->receiver, $this->subject, $this->content, $this->header, $this->params);
	}
	
	/**
	 * Socket发送邮件
	 */
	public function socketSend()
	{
		$contentType = 'multipart/alternative';
		
		if ($this->related || $this->attachments) {
			$contentType = 'multipart/mixed';
		}
		
		$this->header 	= $this->getHeader($contentType);
		$this->content 	= $this->getContent();
		
		$socketEmailObj = new SocketEmail(
			$this->sender, 	 $this->host, 	  $this->port, 
			$this->_username, $this->_password
		);

		return $socketEmailObj->send($this->receiver, $this->header, $this->content);
	}

	/**
	 * 新建boundary边界符
	 * @param integer $num
	 */
	protected function newBoundary($num)
	{
		srand(time()* intval($num));
		return md5(mt_rand());
	}

	/**
	 * 生成邮件头
	 * @param string $contentType
	 */
	protected function getHeader($contentType)
	{
		$header  = "From: {$this->sender}<{$this->sender}>" . OC_ENTER;
		$header .= $this->replyTo ? "Reply-To: {$this->replyTo}" . OC_ENTER : false;
		$header .= "To: {$this->receiver}" . OC_ENTER;
		$header .= $this->cc . $this->bcc;
		$header .= "Subject: {$this->subject}" . OC_ENTER;
		$header .= "Mime-Version: 1.0" . OC_ENTER;
		$header .= "Content-Type: {$contentType}; boundary=\"{$this->boundary}\"" . OC_ENTER;
		
		return $header . "\r\n";
	}

	/**
	 * 生成邮件体
	 */
	protected function getContent()
	{
		$content = false;
		
		if ($this->related || $this->attachments) {
			$subBoundary 	  = $this->newBoundary(2);
			$thirdSubBoundary = $this->newBoundary(3);
			
			$content .= "--{$this->boundary}" . OC_ENTER;
			$content .= "Content-type: multipart/related; type=\"multipart/alternative\"; boundary=\"{$subBoundary}\"" . OC_ENTER;
			$content .= OC_ENTER . "--{$subBoundary}" . OC_ENTER;
			$content .= "Content-type: multipart/alternative; boundary=\"{$thirdSubBoundary}\"" . OC_ENTER;
			$content .= $this->getText($thirdSubBoundary);
			$content .= OC_ENTER . "--{$subBoundary}--" . OC_ENTER;
			
			if ($this->attachments) {
				$content .= $this->attachments;
			}
			$content .= OC_ENTER;
		} else {
			$content .= $this->getText($this->boundary);
		}
		
		$content .= OC_ENTER . "--{$this->boundary}--" . OC_ENTER;
		
		return $content . OC_ENTER;
	}

	/**
	 * 生成纯文本和HTML段
	 * @param string $subBoundary
	 */
	protected function getText($subBoundary)
	{
		$content = false;
		
		if ($this->text) {
			$content .= "--{$subBoundary}" . OC_ENTER;
			$content .= $this->text . OC_ENTER;
		}
		
		if ($this->html) {
			$content .= "--{$subBoundary}" . OC_ENTER;
			$content .= $this->html  . OC_ENTER;
		}
		
		return OC_ENTER . $content  . OC_ENTER;
	}
	
	/**
	 * 组合抄送人
	 * @param array $cc
	 */
	protected function packCc($cc = false)
	{
		if ($cc) {
			$this->cc = implode(',', $cc);
		}
		
		return $this->cc ? "Cc: {$this->cc}" . OC_ENTER : false;
	}

	/**
	 * 组合秘密抄送人 
	 * @param array $bcc
	 */
	protected function packBcc($bcc = false)
	{
		if ($bcc) {
			$this->bcc = implode(',', $bcc);
		}
		
		return $this->bcc ? "Bcc: {$this->bcc}" . OC_ENTER: false;
	}
	
	/**
	 * 设置回复地址
	 * @param string $replyTo
	 */
	public function setReplyTo($replyTo = false)
	{
		$this->replyTo = $replyTo ? $replyTo : $this->sender;
	}
	
	/**
	 * 不设置回复地址
	 */
	public function noReply()
	{
		$this->replyTo = null;
	}
	
	/**
	 * 设置纯文本
	 * @param string $text
	 * @param string $charset
	 * @param string $encoding
	 */
	public function setText($text, $charset = 'UTF-8', $encoding = 'quoted-printable')
	{
		$content  = "Content-Type: text/plain; charset=\"{$charset}\"" . OC_ENTER;
		$content .= "Content-Transfer-Encoding: {$encoding}" . OC_ENTER;
		$content .= OC_ENTER . "{$text}" . OC_ENTER;
		
		$this->text = $content;
	}

	/**
	 * 设置HTML内容
	 * @param string $html
	 * @param string $charset
	 * @param string $encoding
	 */
	public function setHtml($html, $charset = 'UTF-8', $encoding = 'quoted-printable')
	{
		$content = "Content-Type: text/html; charset=\"{$charset}\"" . OC_ENTER;
		$content .= "Content-Transfer-Encoding: {$encoding}" . OC_ENTER;
		$content .= OC_ENTER . "{$html}" . OC_ENTER;
		
		$this->html = $content . OC_ENTER;
	}

	/**
	 * 设置内嵌资源（暂不支持）
	 */
	public function setRelated()
	{
		$this->related = false;
	}

	/**
	 * 设置附件
	 * @param array $attachments
	 */
	public function setAttachment($attachments = false)
	{
		$content = false;
		$attachments = ocForceArray($attachments);
		
		if (empty($attachments)) return false;
		
		foreach ($attachments as $file) 
		{
			if (ocFileExists($file) && $fileContent = ocRead($file)) 
			{
				$basename = ocBasename($file);
				$extName = strrchr($basename, '.');
				$content .= OC_ENTER . "--{$this->boundary}" . OC_ENTER;
				$content .= "Content-type:" . $this->getMimeType($extName) . ";name={$basename}" . OC_ENTER;
				$content .= "Content-Disposition: attachment;filename={$basename}" . OC_ENTER;
				$content .= "Content-Transfer-Encoding: base64" . OC_ENTER . OC_ENTER;
				$content .= chunk_split(base64_encode($fileContent)) . OC_ENTER;
			}
		}
		
		$this->attachments = $content . OC_ENTER;
	}

	/**
	 * 获取附件MIME类型
	 * @param string $extName
	 */
	public function getMimeType($extName)
	{
		switch ($extName) {
			case ".gif":
				return "image/gif";
			case ".jpg":
				return "image/jpeg";
			case ".gz":
				return "application/x-gzip";
			case ".htm":
				return "text/html";
			case ".html":
				return "text/html";
			case ".tar":
				return "application/x-tar";
			case ".txt":
				return "text/plain";
			case ".zip":
				return "application/zip";
			default:
				return "application/octet-stream";
		}
	}
}
