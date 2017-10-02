<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架  智能回调类Call
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara;

use Ocara\Exception\Exception;

defined('OC_PATH') or exit('Forbidden!');

final class Call extends Base
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
		}
		return self::$_instance;
	}

	/**
	 * 运行函数、类方法或路由，返回值
	 * @param string $route
	 * @param array $params
	 * @param boolean $required
	 */
	public static function run($route, array $params = array(), $return = true,$required = false)
	{
		if (empty($route)) return true;

		if (is_string($route)) {
			return self::_runByString($route, $params, $return);
		} elseif (is_array($route)) {
			return self::_runByArray($route, $params, $return);
		}

		if ($required) {
			self::_throwError('invalid_call_func');
		}

		return null;
	}

	/**
	 * 字符串调用
	 * @param string $route
	 * @param array $params
	 * @param bool $return
	 */
	private static function _runByString($route, array $params = array(), $return = true)
	{
		if (preg_match('/^\/?\w+(\/\w+)+$/', $route, $mt)) {
			$route = Ocara::parseRoute($route);
			return Ocara::boot($route, $return, $params);
		}

		return call_user_func_array($route, $params);
	}

	/**
	 * 数组调用
	 * @param array $route
	 * @param array $params
	 */
	private static function _runByArray(array $route, array $params)
	{
		$route = array_values($route);

		if (count($route) < 2 || !is_string($route[1])) {
			self::_throwError('invalid_call_func');
		} else {
			if (is_object($route[0])) {
				if (method_exists($route[0], $route[1])) {
					return call_user_func_array($route, $params);
				}
				self::_throwError('not_exists_method', array($route[1]));
			} else {
				return Ocara::boot($route, true, $params);
			}
		}
	}

	/**
	 * 输出错误
	 * @param string $error
	 * @param array $params
	 */
	private static function _throwError($error, array $params = array())
	{
		$error = Lang::get($error, $params);
		throw new Exception($error['message'], $error['code']);
	}
}