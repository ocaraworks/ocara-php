<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架   HTTP请求数据处理类Request
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara\Core;

use Ocara\Core\Base;

defined('OC_PATH') or exit('Forbidden!');

class Request extends Base
{
	public function __construct()
	{
		$this->setInputStreams();
		$this->stripslashes();
	}

	/**
	 * 处理输入流
	 */
	public function setInputStreams()
	{
		if ($_POST || !($post = ocRemote('php://input'))) return;

		if (is_array($post)) {
			$_POST = $post;
		} elseif(is_string($post)) {
			if ($_SERVER['CONTENT_TYPE'] == 'application/json') {
				$_POST = json_decode($post, true);
			} else {
				parse_str($post, $_POST);
			}
		}
	}

	/**
	 * 初始化去除转义或Ocara标签
	 */
	public function stripslashes()
	{
		$func  	 = get_magic_quotes_gpc() ? 'cleanSqlTag' : 'stripSqlTag';
		$_GET  	 = ocArrayMap(array($this, $func), $_GET);
		$_POST   = ocArrayMap(array($this, $func), $_POST);
		$_COOKIE = ocArrayMap(array($this, 'stripSqlTag'), $_COOKIE);
	}

	/**
	 * 去除SQL标签
	 * @param string|number $content
	 * @return mixed
	 */
	public function stripSqlTag($content)
	{
		if (is_numeric($content) || is_string($content)) {
			$content = str_ireplace(OC_SQL_TAG, OC_EMPTY, $content);
		}
		
		return $content;
	}

	/**
	 * 去除SQL标签和转义
	 * @param string $content
	 * @return mixed
	 */
	public function cleanSqlTag($content)
	{
		if (is_numeric($content) || is_string($content)) {
			$content = str_ireplace(OC_SQL_TAG, OC_EMPTY, stripslashes($content));
		}
		
		return $content;
	}
	
	/**
	 * 判断是否是GET请求
	 */
	public function isGet()
	{
		return $this->getMethod() == 'GET';
	}

	/**
	 * 判断是否是POST请求
	 */
	public function isPost()
	{
		return $this->getMethod() == 'POST';
	}

	/**
	 * 判断是否是PUT请求
	 */
	public function isPut()
	{
		return $this->getMethod() == 'PUT';
	}

	/**
	 * 判断是否是PUT请求
	 */
	public function isPatch()
	{
		return $this->getMethod() == 'PATCH';
	}

	/**
	 * 判断是否是DELETE请求
	 */
	public function isDelete()
	{
		return $this->getMethod() == 'DELETE';
	}

	/**
	 * 判断是否是PUT请求
	 */
	public function isHead()
	{
		return $this->getMethod() == 'HEAD';
	}

	/**
	 * 判断是否是OPTIONS请求
	 */
	public function isOptions()
	{
		return $this->getMethod() == 'OPTIONS';
	}

	/**
	 * 判断是否是TRACE请求
	 */
	public function isTrace()
	{
		return $this->getMethod() == 'TRACE';
	}

	/**
	 * 判断是否是CONNECT请求
	 */
	public function isConnect()
	{
		return $this->getMethod() == 'CONNECT';
	}

	/**
	 * 判断是否是AJAX请求
	 */
	public function isAjax()
	{
		return isset($_SERVER['HTTP_X_REQUESTED_WITH'])
				&& strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest'
			    || !empty($_GET['oc_ajax']);
	}

	/**
	 * 手动设置为AJAX请求
	 */
	public function setAjax()
	{
		$_SERVER['HTTP_X_REQUESTED_WITH'] = 'xmlhttprequest';
		if (isset($_GET['oc_ajax'])) {
			$_GET['oc_ajax'] = true;
		}
	}

	/**
	 * 获取请求方式
	 * @return null
	 */
	public function getMethod()
	{
		if (PHP_SAPI == 'cli') {
			$method = strtoupper(ocGet('argv.2', $_SERVER));
			if (in_array($method, ocConfig('ALLOWED_HTTP_METHODS'))) {
				return $method; 
			}
		}

		if (isset($_SERVER['REQUEST_METHOD'])) {
			return $_SERVER['REQUEST_METHOD'];
		}

		return 'GET';
	}

	/**
	 * 获取GET参数值
	 * @param string $key
	 * @param string|array $default
	 * @return array|null|string
	 */
	public function getGet($key = null, $default = null)
	{
		return $this->getRequestValue($_GET, $key, $default);
	}

	/**
	 * 获取POST参数值
	 * @param string $key
	 * @param string $default
	 * @return array|null|string
	 */
	public function getPost($key = null, $default = null)
	{
		return $this->getRequestValue($_POST, $key, $default);
	}

	/**
	 * 获取COOKIE参数值
	 * @param string $key
	 * @param string|array $default
	 * @return array|null|string
	 */
	public function getCookie($key = null, $default = null)
	{
		return $this->getRequestValue($_COOKIE, $key, $default);
	}

	/**
	 * 获取REQUEST参数值
	 * @param string $key
	 * @param string|array $default
	 * @return array|null|string
	 */
	public function getRequest($key = null, $default = null)
	{		
		return $this->getRequestValue($_REQUEST, $key, $default);
	}

	/**
	 * 获取值
	 * @param $data
	 * @param string $key
	 * @param string|array $default
	 * @return array|null|string
	 */
	public function getRequestValue(array $data, $key = null, $default = null)
	{
		if ($key === null) {
			$data = ocArrayMap('trim', $data);
			return ocService()->filter->request($data);
		}
		if(array_key_exists($key, $data)) {
			if (is_array($data[$key])) {
				$value = ocArrayMap('trim', $data[$key]);
			} else {
				$value = trim($data[$key]);
			}
			if (ocEmpty($value) && $default !== null) {
				return $default;
			}
			return ocService()->filter->request($value);
		}

		return $default === null ? OC_EMPTY : $default;
	}

	/**
	 * 获取Server参数值
	 * @param string $key
	 * @param string $default
	 * @return null|string
	 */
	public function getServer($key = null, $default = null)
	{
		if ($key === null) {
			return $_SERVER;
		}
		
		if (array_key_exists($key, $_SERVER)) {
			return $_SERVER[$key];
		}  
		
		return $default === null ? OC_EMPTY : $default;
	}

	/**
	 * 解析Json参数
	 * @param string $param
	 * @return array|bool|\mix|mixed|\stdClass|string
	 */
	public function decodeJson($param)
	{
		return json_decode(json_decode(html_entity_decode(stripslashes($param))));
	}
}