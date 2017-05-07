<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架   URL类Url
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara;
use Ocara\Ocara;

defined('OC_PATH') or exit('Forbidden!');

final class Url extends Base
{
	const DEFAULT_TYPE 	= 1; //默认类型
	const DIR_TYPE 		= 2; //伪目录类型
	const PATH_TYPE 	= 3; //伪路径类型
	const STATIC_TYPE 	= 4; //伪静态类型
	
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
		}
		return self::$_instance;
	}
	
	/**
	 * 是否虚拟URL地址
	 * @param string $urlType
	 */
	public static function isVirtualUrl($urlType)
	{
		return in_array($urlType, array(self::DIR_TYPE, self::PATH_TYPE, self::STATIC_TYPE));
	}

	/**
	 * URL请求参数解析
	 * @param string $url
	 */
	public static function parseGet($url = false)
	{
		if (empty($url)) {
			if (OC_PHP_SAPI == 'cli') {
				$url = trim(ocGet('argv.1', $_SERVER), OC_DIR_SEP);
			} else {
				$localUrl = $_SERVER['DOCUMENT_ROOT'] . OC_REQ_URI;
				if ($localUrl == $_SERVER['SCRIPT_FILENAME']) {
					return array();
				}
				$url = trim(OC_REQ_URI, OC_DIR_SEP);
			}
		}

		if (empty($url)) return array();

		$module = null;
		$result = Url::check($url, OC_URL_ROUTE_TYPE);
		if ($result === null) {
			Error::show('fault_url');
		}

		if (Url::isVirtualUrl(OC_URL_ROUTE_TYPE)) {
			$get = trim($result[3]);

			if ($get) {
				$get = explode(OC_DIR_SEP, trim($result[3], OC_DIR_SEP));
				if($get[0] == OC_DEV_SIGN){
					$module = array_shift($get);
				}
			} else {
				$get[0] = null;
			}
			if (isset($result[11])) {
				parse_str($result[11], $extends);
				$get[] = $extends;
			}
		} else {
			$get[1] = $get[0] = null;
			$params = explode('&', $result[6]);

			foreach ($params as $row) {
				$array = explode('=', $row);
				$key = $array[0];
				$value = isset($array[1]) ? $array[1] : false;
				if ($key == 'm' && $module === null) {
					$module = $value ? $value : false;
				} elseif ($key == 'c' && $get[0] === null) {
					$get[0] = $value;
				} elseif ($key == 'a' && $get[1] === null) {
					$get[1] = $value;
				} else {
					$get[$key] = $value;
				}
			}
		}

		array_unshift($get, $module);
		return $get;
	}

	/**
	 * 检测URL
	 * @param string $url
	 * @param string $urlType
	 */
	public static function check($url, $urlType)
	{
		$url = str_replace(DIRECTORY_SEPARATOR, OC_DIR_SEP, $url);
		$el  = '[^\/\&\?]';

		if (self::isVirtualUrl($urlType)) {
			$str  = $urlType == self::PATH_TYPE ? 'index\.php[\/]?' : false;
			$el   = '[^\/\&\?]';
			$mvc  = '\w*';
			$mvcs = $mvc . '\/';

			if ($urlType == self::STATIC_TYPE && $url != OC_DIR_SEP) {
				$file = "\.html?";
			} else {
				$file = OC_EMPTY;
			}

			$tail = "(\/\w+\.\w+)?";
			$tail = $file . "({$tail}\?(\w+={$el}*(&\w+={$el}*)*)?(#.*)?)?";
			$exp  = "/^(\w+:\/\/\w+(\.\w)*)?{$str}(({$mvc})|({$mvcs}{$mvc})|({$mvcs}{$mvcs}{$mvc}(\/({$el}*\/?)+)*))?{$tail}$/i";
		} else {
			$p = '[a-zA-Z_][\w]*';
			$v = '[\w]*';
			$m = "(m={$v})";

			$cap = "(c={$v}(\&a={$v}(\&{$p}={$el}*)*)?)";
			$exp = "/^(\w+:\/\/\w+(\.\w*)*)?(\/?(\w*\/)*)?index\.php(\?({$m}|{$cap}|({$m}\&{$cap}))?)?$/";
		}

		if (preg_match($exp, $url, $mt)){
			return $mt;
		}

		return  null;
	}

	/**
	 * 新建URL
	 * @param string|array $route
	 * @param string|array $params
	 * @param bool $relative
	 * @param integer $urlType
	 * @param bool $static
	 */
	public static function create($route, $params = array(), $relative = false, $urlType = false, $static = true)
	{
		extract(Ocara::parseRoute($route));
		if (empty($route)) return false;

		$urlType = $urlType ? $urlType : OC_URL_ROUTE_TYPE;

		if (is_numeric($params) || is_string($params)) {
			$array = array_chunk(explode(OC_DIR_SEP, $params), 2);
			$params = array();
			foreach ($array as $value) {
				$params[reset($value)] = isset($value[1]) ? $value[1] : null;
			}
		} elseif (!is_array($params)) {
			$params = array();
		}

		if ($static && StaticPath::$open) {
			list($file, $args) = StaticPath::getStaticFile($module, $controller, $action, $params);
			if ($file && is_file(ocPath('static', $file))) {
				return $relative ? OC_DIR_SEP . $file : OC_ROOT_URL . $file;
			}
		}
		
		if (self::isVirtualUrl($urlType)) {
			if ($module) {
				$query = array($module, $controller, $action);
			} else {
				$query = array($controller, $action);
			}
			
			$route     = implode(OC_DIR_SEP, $query);
			$query     = $params ? OC_DIR_SEP . implode(OC_DIR_SEP, self::devideQuery($params)) : false;
			$paramPath = $urlType == self::PATH_TYPE ? OC_INDEX_FILE . OC_DIR_SEP : false;
			$paramPath = $paramPath . $route . $query;
			$paramPath = $urlType == self::STATIC_TYPE ? $paramPath . '.html' : $paramPath;
		} else {
			$route = $query = array();
			if ($module) {
				$route['m'] = $module;
			}

			$route['c'] = $controller;
			$route['a'] = $action;

			foreach ($route as $key => $value) {
				$query[] = $key . '=' . $value;
			}
			foreach ($params as $key => $value) {
				$query[] = $key . '=' . $value;
			}

			$paramPath = OC_INDEX_FILE . '?' . implode('&', $query);
		}
		
		return $relative ? OC_DIR_SEP . $paramPath : OC_ROOT_URL . $paramPath;
	}

	/**
	 * 格式化参数数组
	 * @param array $params
	 */
	public static function devideQuery(array $params)
	{
		$result = array();
		
		if ($params) {
			if (0) return array_values($params);
			foreach ($params as $key => $value) {
				$result[] = $key;
				$result[] = $value;
			}
		}
		
		return $result;
	}

	/**
	 * 添加查询字符串参数
	 * @param array $params
	 * @param string $url
	 * @param integer $urlType
	 */
	public static function addQuery(array $params, $url = false, $urlType = false)
	{
		$urlType = $urlType ? $urlType : OC_URL_ROUTE_TYPE;
		$data    = self::parseUrl($url);
		
		if ($url) {
			$uri = $data['path'] . ($data['query'] ? '?' . $data['query'] : false);
		} else {
			$uri = OC_REQ_URI;
		}

		$result = self::check($uri, $urlType);
		if ($result === null) {
			Error::show('fault_url');
		}

		if (self::isVirtualUrl($urlType)) {
			$data['path'] = $result[3] . OC_DIR_SEP . implode(OC_DIR_SEP, self::devideQuery($params));
		} else {
			parse_str($data['query'], $query);
			$data['query'] = self::buildQuery($query, $params);
		}
		
		return self::buildUrl($data);
	}

	/**
	 * 解析URL
	 * @param string $url
	 */
	public static function parseUrl($url = false)
	{
		$fields = array(
			'scheme', 	'host', 	'port',
			'username', 'password',  
			'path', 	'query',
		);

		if ($url) {
			$values = array_fill(0, 7, null);
			$data  = array_combine($fields, $values);
			$data  = array_merge($data, parse_url($url));
		} else {
			$values = array(
				OC_PROTOCOL, 
				Request::getServer('HTTP_HOST'),
				Request::getServer('SERVER_PORT'),
				Request::getServer('PHP_AUTH_USER'),
				Request::getServer('PHP_AUTH_PW'),
				Request::getServer('REDIRECT_URL'),
				Request::getServer('QUERY_STRING'),
			);
			$data = array_combine($fields, $values);
		}
		
		return $data;
	}
	
	/**
	 * 生成查询字符串
	 * @param array $params
	 */
	public static function buildQuery(array $params)
	{
		$array = array();
		
		foreach ($params as $key => $value) {
			$array[] = $key . '=' . $value;
		}
		
		return implode('&', $array);
	}

	/**
	 * 生成URL
	 * @param array $data
	 */
	public static function buildUrl(array $data)
	{
		$url = $data['scheme'] . '://';
		if ($data['username']) {
			$url = $url . "{$data['username']}:{$data['password']}@";
		}

		$url = $url . $data['host'];
		if ($data['port']) {
			$url = $url . ($data['port'] == '80' ? false : ':' . $data['port']);
		}

		$url = $url . $data['path'];
		if ($data['query']) {
			$url = $url . '?' . $data['query'];
		}
		
		return $url;
	}
}