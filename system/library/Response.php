<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架   HTTP响应类Response
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara;
use Ocara\Service\Xml;

defined('OC_PATH') or exit('Forbidden!');

class Response extends Base
{
	const STATUS_OK = 200;
	const STATUS_CREATED = 201;
	const STATUS_NO_CONTENT = 204;
	const STATUS_MOVED = 301;
	const STATUS_BAD_REQUEST = 400;
	const STATUS_UNAUTHORIZED = 401;
	const STATUS_FORBIDDEN = 403;
	const STATUS_NOT_FOUND = 404;
	const STATUS_NOT_ALLOWED = 405;
	const STATUS_GONE = 410;
	const STATUS_UNSUPPORTED_TYPE = 415;
	const STATUS_SERVER_ERROR = 500;
	const STATUS_NOT_IMPLEMENTED = 501;
	const STATUS_SERVICE_UNAVAILABLE = 503;

	private $_headers = array();

	/**
	 * 发送头部信息
	 * @param string|array $headers
	 */
	public function sendHeaders($headers = null)
	{
		if (headers_sent()) return null;

		$data = array();
		if (isset($this->_headers['statusCode'])) {
			$data[] = $this->_getStatusCode();
		}

		if (isset($this->_headers['contentType'])) {
			$data[] = $this->_getContentType();
		}

		if ($headers) {
			$data = array_merge($data, (array)$headers);
		}

		foreach ($data as $header) {
			header($header);
		}
	}

	/**
	 * 设置状态
	 * @param $code
	 */
	public function setStatusCode($code)
	{
		$httpStatus = ocConfig('HTTP_STATUS');
		if (array_key_exists($code, $httpStatus)) {
			$this->_headers['statusCode'] = $code;
		}
	}

	/**
	 * 设置响应文档类型
	 * @param string $contentType
	 */
	public function setContentType($contentType)
	{
		$this->_headers['contentType'] = $contentType;
	}

	/**
	 * 设置响应文档编码
	 * @param string $charset
	 */
	public function setCharset($charset = 'utf-8')
	{
		$this->_headers['charset'] = $charset;
	}

	/**
	 * 获取头部设置
	 * @param $name
	 * @return mixed|null
	 */
	public function get($name)
	{
		if (isset($this->_headers[$name])) {
			return $this->_headers[$name];
		}
		return null;
	}

	/**
	 * 返回状态码
	 */
	public function _getStatusCode()
	{
		$httpStatus = ocConfig('HTTP_STATUS');
		$result = $httpStatus[$this->_headers['statusCode']];
		return $result;
	}

	/**
	 * 设置返回内容类型
	 */
	public function _getContentType()
	{
		if (isset($this->_headers['contentType'])) {
			$contentType = $this->_headers['contentType'];
		} else {
			$contentType = ocConfig('DEFAULT_RESPONSE_TYPE', 'html');
		}

		$contentType = strtolower($contentType);
		$mineTypes = ocConfig('MINE_TYPES');
		if (array_key_exists($contentType, $mineTypes)) {
			$contentType = $mineTypes[$contentType];
		}

		if (!empty($this->_headers['charset'])) {
			$charset = $this->_headers['charset'];
		} else {
			$charset = 'utf-8';
		}

		$result = "Content-Type:{$contentType}; charset={$charset}";
		return $result;
	}

	/**
	 * 输出内容
	 * @param $content
	 * @param bool $unEscaped
	 */
	public function output($content, $unEscaped = true)
	{
		$contentType = $this->get('contentType');
		switch ($contentType)
		{
			case 'json':
				if ($unEscaped && defined('JSON_UNESCAPED_UNICODE')) {
					$content = json_encode($content, JSON_UNESCAPED_UNICODE);
				} else {
					$content = json_encode($content);
				}
				break;
			case 'xml':
				$content = self::getXmlResult($content);
				break;
		}
		echo $content;
	}

	/**
	 * 内部路由跳转
	 * @param array|string $route
	 * @param array $params
	 * @param bool $die
	 */
	public static function jump($route, array $params = array(), $die = true)
	{
		if ($route) {
			if (!headers_sent()) {
				header_remove('location');
				header('location:' . ocUrl($route, $params));
			}
			$die && die();
		} else {
			Error::show('not_null', array('route'));
		}
	}

	/**
	 * 外部跳转
	 * @param string $url
	 * @param bool $die
	 */
	public static function redirect($url, $die = true)
	{
		if ($url) {
			if (!headers_sent()) {
				header_remove('location');
				header('location:' . $url);
			}
			$die && die();
		} else {
			Error::show('not_null', array('url'));
		}
	}

	/**
	 * 获取XML结果
	 */
	private static function getXmlResult($result)
	{
		$xmlObj = new Xml();
		$xmlObj->setData('array', array('root', $result));
		$xml = $xmlObj->getContent();

		return $xml;
	}
}