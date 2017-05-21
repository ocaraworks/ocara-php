<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架   AJAX请求处理类Ajax
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara;

defined('OC_PATH') or exit('Forbidden!');

final class Ajax extends Base
{
	/**
	 * 单例模式
	 */
	private static $_instance;

	private function __clone(){}
	private function __construct(){}

	public static function getInstance()
	{
		if (self::$_instance === null) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	 * 输出结果
	 * @param string $status
	 * @param array $message
	 * @param string|array $body
	 * @param bool $contentType
	 */
	public static function show($status, array $message = array(), $body = OC_EMPTY)
	{
		if (is_string($message)) {
			$message = Lang::get($message);
		}

		$result['status'] 	= $status;
		$result['code']    	= $message['code'];
		$result['message'] 	= $message['message'];
		$result['body']    	= $body;

		if ($callback = ocConfig('CALLBACK.ajax_return', null)) {
			$result = Call::run($callback, array($result));
		}

		$response = self::$container->response;
		$data = $response->prepareSendHeaders();
		$contentType = $response->getHeader('contentType');
		$response->sendHeaders($data);

		switch ($contentType)
		{
			case 'json':
				$content = json_encode($result);
				break;
			case 'xml':
				$content = self::getXmlResult($result);
				break;
		}

		echo($content);
	}
}