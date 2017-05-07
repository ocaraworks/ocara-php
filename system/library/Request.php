<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架   HTTP请求数据处理类Request
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara;

defined('OC_PATH') or exit('Forbidden!');

final class Request extends Base
{

	/**
	 * 单例模式
	 */
	private static $_instance = null;

	private function __clone(){}
	private function __construct(){}

	public static function getInstance()
	{
		if (self::$_instance === null) {
			self::$_instance = new self();
			self::setInputStreams();
			self::stripslashes();
		}
		return self::$_instance;
	}

	/**
	 * 处理输入流
	 */
	public static function setInputStreams()
	{
		if ($_POST OR !$post = ocRemote('php://input')) return;

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
	public static function stripslashes()
	{
		$func  	 = get_magic_quotes_gpc() ? 'cleanData' : 'stripOcaraTag';
		$_GET  	 = ocArrayMap(array(__CLASS__, $func), $_GET);
		$_POST   = ocArrayMap(array(__CLASS__, $func), $_POST);
		$_COOKIE = ocArrayMap(array(__CLASS__, 'stripOcaraTag'), $_COOKIE);
	}

	/**
	 * 去除Ocara标签
	 * @param string $content
	 */
	public static function stripOcaraTag($content)
	{
		if (is_numeric($content) || is_string($content)) {
			$content = str_ireplace('{oc_sql}', OC_EMPTY, $content);
		}
		
		return $content;
	}
	
	/**
	 * 去除Ocara标签和转义
	 * @param string $content
	 */
	public static function cleanData($content)
	{
		if (is_numeric($content) || is_string($content)) {
			$content = str_ireplace('{oc_sql}', OC_EMPTY, stripslashes($content));
		}
		
		return $content;
	}
	
	/**
	 * 判断是否是GET请求
	 */
	public static function isGet()
	{
		return isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'GET';
	}

	/**
	 * 判断是否是POST请求
	 */
	public static function isPost()
	{
		return isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'POST';
	}

	/**
	 * 判断是否是PUT请求
	 */
	public static function isPut()
	{
		return isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'PUT';
	}

	/**
	 * 判断是否是PUT请求
	 */
	public static function isPatch()
	{
		return isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'PATCH';
	}

	/**
	 * 判断是否是DELETE请求
	 */
	public static function isDelete()
	{
		return isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'DELETE';
	}

	/**
	 * 判断是否是PUT请求
	 */
	public static function isHead()
	{
		return isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'HEAD';
	}

	/**
	 * 判断是否是OPTIONS请求
	 */
	public static function isOptions()
	{
		return isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'OPTIONS';
	}

	/**
	 * 判断是否是TRACE请求
	 */
	public static function isTrace()
	{
		return isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'TRACE';
	}

	/**
	 * 判断是否是CONNECT请求
	 */
	public static function isConnect()
	{
		return isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'CONNECT';
	}

	/**
	 * 判断是否是AJAX请求
	 */
	public static function isAjax()
	{
		return isset($_SERVER['HTTP_X_REQUESTED_WITH'])
				&& strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest'
			    || !empty($_GET['oc_ajax']);
	}

	/**
	 * 手动设置为AJAX请求
	 */
	public static function setAjax()
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
	public static function getMethod()
	{
		if (isset($_SERVER['REQUEST_METHOD'])) {
			return $_SERVER['REQUEST_METHOD'];
		}

		return null;
	}

	/**
	 * 获取GET参数值
	 * @param string $key
	 * @param string $default
	 */
	public static function getGet($key = null, $default = null)
	{
		return self::getRequestValue($_GET, $key, $default);
	}

	/**
	 * 获取POST参数值
	 * @param string $key
	 * @param string $default
	 */
	public static function getPost($key = null, $default = null)
	{
		return self::getRequestValue($_POST, $key, $default);
	}

	/**
	 * 获取COOKIE参数值
	 * @param string $key
	 * @param string $default
	 */
	public static function getCookie($key = null, $default = null)
	{
		return self::getRequestValue($_COOKIE, $key, $default);
	}

	/**
	 * 获取REQUEST参数值
	 * @param string $key
	 * @param string $default
	 */
	public static function getRequest($key = null, $default = null)
	{		
		return self::getRequestValue($_REQUEST, $key, $default);
	}

	/**
	 * 获取值
	 * @param array $data
	 * @param string $key
	 * @param string $default
	 */
	public static function getRequestValue($data, $key = null, $default = null)
	{
		if ($key === null) {
			$data = ocArrayMap('trim', $data);
			return Filter::request($data);
		}

		if(array_key_exists($key, $data)) {
			if (is_array($data[$key])) {
				$value = ocArrayMap('trim', $data[$key]);
			} else {
				$value = trim($data[$key]);
			}
			return Filter::request($value);
		}
		
		return $default === null ? OC_EMPTY : $default;
	}

	/**
	 * 获取Server参数值
	 * @param string $key
	 * @param string $default
	 */
	public static function getServer($key = null, $default = null)
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
	 */
	public static function decodeJson($param)
	{
		return json_decode(json_decode(html_entity_decode(stripslashes($param))));
	}
}