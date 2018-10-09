<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架   Socket基础插件SocketBase
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara\Service;

use Ocara\Core\ServiceBase;

class SocketBase extends ServiceBase
{
	public $host;
	public $port;
	public $socket;
	public $conn;
	public $error;

	/**
	 * 析构函数
	 */
	public function __construct()
	{
		ocCheckExtension('sockets');
		self::loadLanguage('OCSocket.php');
	}

	/**
	 * 显示错误
	 * @param string $errorType
	 * @param string $connId
	 */
	protected function _showError($errorType, $connId = 'conn')
	{
		$this->error = $connId === null ? socket_last_error() : socket_last_error($this->$connId);
		
		if (preg_match('/^[\x{4e00}-\x{9fa5}A-Za-z0-9_]+$/u', $this->error)) {
			$errorMsg = iconv('gbk', 'utf-8', socket_strerror($this->error));
		} else {
			$errorMsg = $this->error;
		}
		
		$this->showError('socket_error', array($errorType, $this->error, $errorMsg));
	}
}
