<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架   Socket客户端插件SocketClient
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara\Service;

class SocketClient extends SocketBase
{
	/**
	 * 连接服务端
	 * @param string $host
	 * @param string $port
	 * @param integer $limitTime
	 * @param integer $recTimeout
	 * @param integer $sendTimeout
	 */
	public function connect($host, $port, $limitTime = 0, $recTimeout = 3, $sendTimeout = 2)
	{
		if ($limitTime) @set_time_limit($limitTime);
		
		$this->socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
		socket_set_option(
			$this->socket, SOL_SOCKET, SO_RCVTIMEO, array('sec' => $recTimeout, 'usec' => 0)
		);
		socket_set_option(
			$this->socket, SOL_SOCKET, SO_SNDTIMEO, array('sec' => $sendTimeout, 'usec' => 0)
		);

		$this->conn   = @socket_connect($this->socket, gethostbyname($host), $port);
		if (!$this->conn) {
			$errorCode = socket_last_error();
			$errorMsg = socket_strerror($errorCode);
			$this->_showError('socket_error', array($errorCode, $errorMsg));
		}

		return $this->socket;
	}

	/**
	 * 读取数据
	 * @param integer $length
	 * @param bool $type
	 */
	public function read($length = 512, $type = PHP_BINARY_READ)
	{
		if (empty($this->socket)) {
			$this->showError('no_connect');
		}
		
		$result = @socket_read($this->socket, $length, $type);
		
		if ($result === false) {
			$this->_showError('read');
		}
		
		return $result;
	}

	/**
	 * 发送数据
	 * @param string $content
	 * @param integer $length
	 */
	public function send($content, $length = 0)
	{
		if (empty($this->socket)) {
			$this->showError('no_connect');
		}
		
		$length = $length ? $length : strlen($content);
		$result = @socket_write($this->socket, $content, $length);
		
		if ($result === false) {
			$this->_showError('write');
		}
		
		return $result;
	}

	/**
	 * 关闭当前Socket链接
	 */
	public function shutdown($how = 2)
	{
		if (is_resource($this->socket)) {
			@socket_shutdown($this->socket, $how);
		}
	}

	/**
	 * 关闭当前Socket进程
	 */
	public function close()
	{
		if (is_resource($this->socket)) {
			@socket_close($this->socket);
		}
	}
}
