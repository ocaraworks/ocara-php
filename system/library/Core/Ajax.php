<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架   AJAX请求处理类Ajax
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara\Core;

use Ocara\Core\Base;

defined('OC_PATH') or exit('Forbidden!');

class Ajax extends Base
{
	/**
	 * 输出结果
	 * @param string $status
	 * @param array $message
	 * @param string $body
	 */
	public function show($status, array $message = array(), $body = OC_EMPTY)
	{
	    $services = ocService();
		if (is_string($message)) {
			$message = $services->lang->get($message);
		}

		$result['status'] 	= $status;
		$result['code']    	= $message['code'];
		$result['message'] 	= $message['message'];
		$result['body']    	= $body;

		if ($callback = ocConfig('SOURCE.ajax.return_result', null)) {
			$result = call_user_func_array($callback, array($result));
		}

		$response = $services->response;
		$statusCode = $response->getOption('statusCode');

		if (!$statusCode && !ocConfig('AJAX.response_error_code', 0)) {
			$response->setStatusCode(Response::STATUS_OK);
			$result['statusCode'] 	= $response->getOption('statusCode');
		}

		$contentType = $response->getOption('contentType');
		switch ($contentType)
		{
			case 'json':
				$content = json_encode($result);
				break;
			case 'xml':
				$content = $this->getXmlResult($result);
				break;
		}

		$response->sendHeaders();
		echo($content);
	}

	/**
	 * 获取XML结果
	 */
	private function getXmlResult($result)
	{
		$xmlObj = new Xml();
		$xmlObj->setData('array', array('root', $result));
		$xml = $xmlObj->getContent();

		return $xml;
	}
}