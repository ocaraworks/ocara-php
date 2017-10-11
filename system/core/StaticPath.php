<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架   静态路径生成类StaticPath
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara;

defined('OC_PATH') or exit('Forbidden!');

class StaticPath extends Base
{
	/**
	 * 单例模式
	 */
	private static $_instance = null;

	public static $open;
	public static $fileType;
	public static $route;
	public static $params;
	public static $delimiter;

	private function __clone(){}
	private function __construct(){}

	public static function getInstance()
	{
		if (self::$_instance === null) {
			self::$_instance = new self();
			self::init();
		}
		return self::$_instance;
	}

	/**
	 * 初始化函数
	 */
	private function init()
	{
		self::$open      = ocConfig('STATIC.open', 0);
		self::$fileType  = ocConfig('STATIC.file_type', 'html');
		self::$route     = ocConfig('STATIC.route', null);
		self::$params    = ocConfig('STATIC.params', array());
		self::$delimiter = ocConfig('STATIC.delimiter', '-');
	}

	/**
	 * 获取静态文件
	 * @param string $module
	 * @param string $controller
	 * @param string $action
	 * @param string $data
	 * @return array|bool
	 */
	public static function getStaticFile($module, $controller, $action, $data = null)
	{
		if (empty($controller) || empty($action)) return false;

		$params = self::$params;

		if ($module) {
			if (!array_key_exists($module, $params)) {
				return false;
			}
			$params = $params[$module];
		}
		
		if (!array_key_exists($controller, $params)) {
			return false;
		}

		$params = $params[$controller];
		if (!array_key_exists($action, $params)) {
			return false;
		}
		
		$params = $params[$action];
		$mvcPathMap = self::getMvcPathMap($module, $controller, $action);
		list($file, $param) = self::getParamsPathMap($params, $module, $controller, $action, $data);
		$file = str_ireplace('{p}', $file, $mvcPathMap);

		return array($file, $param);
	}

	/**
	 * 获取参数
	 * @param integer $offset
	 * @param array $params
	 * @param array $data
	 * @param string $paramsStr
	 * @return array
	 * @throws Exception\Exception
	 */
	private static function getParams($offset, $params, $data, $paramsStr)
	{
		$paramData = array();

		foreach ($params as $key => $param) {
			if (preg_match('/^({([\w:]+)})$/i', $param, $mt)) {
				$param = explode(':', trim($mt[2], ':'));
				if (count($param) > 1) {
					$name = $param[0];
					$field = $param[1];
				} else {
					$name = $field = $param[0];
				}
				if (is_array($data)) {
					if (array_key_exists($field, $data) || array_key_exists($field = $name, $data)) {
						$value = urlencode($data[$field]);
					} else {
						$value = false;
					}
				} else {
					$getKey = (integer)$key + $offset;
					$value  = ($value = Request::getGet($getKey)) ? urlencode($value) : false;
				}
				$paramsStr = trim(str_ireplace($mt[1], $value, $paramsStr), self::$delimiter);
				$paramData[$name] = $value;
			} else
				Error::show('fault_static_field');
		}

		return array($paramsStr, $paramData);
	}

	/**
	 * 获取参数数据路径 
	 * @param array $params
	 * @param string $module
	 * @param string $controller
	 * @param string $action
	 * @param array $data
	 */
	public static function getParamsPathMap($params, $module, $controller, $action, $data)
	{
		$extensionName = '.' . self::$fileType;

		$offset     = $module ? 3 : 2;
		$index 		= strrpos($params, OC_DIR_SEP);
		$pathStr 	= str_replace(self::$delimiter, OC_DIR_SEP, substr($params, 0, $index));
		$fileStr 	= substr($params, $index ? $index + 1 : 0);

		if (!preg_match('/^{[\w:]+}(' . self::$delimiter . '{[\w:]+})*$/', $fileStr)) {
			Error::show('fault_static_field');
		}

		$pathParams = $pathStr ? explode(OC_DIR_SEP, trim($pathStr, OC_DIR_SEP)) : array();
		$fileParams = $fileStr ? explode(self::$delimiter, trim($fileStr, self::$delimiter)) : array();

		list($pathStr, $paramData) = self::getParams($offset, $pathParams, $data, $pathStr, true);
		list($fileStr, $paramData) = self::getParams($offset + count($pathParams), $fileParams, $data, $fileStr);

		$path = ($fileStr ? $fileStr : $action) . $extensionName;
		return array($path, $paramData);
	}

	/**
	 * 获取MVC路径
	 * @param string $module
	 * @param string $controller
	 * @param string $action
	 */
	public static function getMvcPathMap($module, $controller, $action)
	{
		if (self::$route && preg_match('/^{c}[\/-]{a}[\/-]{p}$/i', self::$route)) {
			$search  = array('{c}', '{a}');
			$replace = array($controller, $action);
			$module  = $module ? $module . OC_DIR_SEP : false;
			return $module . str_ireplace($search, $replace, self::$route);
		}

		Error::show('fault_static_route');
	}
}