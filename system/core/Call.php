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

class Call extends Base
{
	/**
	 * 运行函数、类方法或路由，返回值
	 * @param mixed $route
	 * @param array $params
	 * @param bool $return
	 * @param bool $required
	 * @return bool|mixed|null
	 * @throws Exception
	 */
	public function run($route, array $params = array(), $return = true,$required = false)
	{
		if (empty($route)) return true;

		if (is_string($route)) {
			return $this->_runByString($route, $params, $return);
		} elseif (is_array($route)) {
			return $this->_runByArray($route, $params, $return);
		}

		if ($required) {
			$this->_throwError('invalid_call_func');
		}

		return null;
	}

	/**
	 * 字符串调用
	 * @param string $route
	 * @param array $params
	 * @param bool $return
	 * @return mixed
	 */
	protected function _runByString($route, array $params = array(), $return = true)
	{
		if (preg_match('/^\/?\w+(\/\w+)+$/', $route, $mt)) {
			$route = Ocara::parseRoute($route);
			return Bootstrap::run($route, $return, $params);
		}

		return call_user_func_array($route, $params);
	}

	/**
	 * 数组调用
	 * @param array $route
	 * @param array $params
	 * @return mixed
	 * @throws Exception
	 */
	protected function _runByArray(array $route, array $params)
	{
		$route = array_values($route);

		if (count($route) < 2) {
			$this->_throwError('invalid_call_func');
		} else {
			if (is_object($route[0])) {
				if (method_exists($route[0], $route[1])) {
					return call_user_func_array($route, $params);
				}
				$this->_throwError('not_exists_method', array($route[1]));
			} else {
				return call_user_func_array($route, $params);
			}
		}
	}

	/**
	 * 输出错误
	 * @param string $error
	 * @param array $params
	 * @throws Exception
	 */
	protected function _throwError($error, array $params = array())
	{
		$error = Ocara::services()->lang->get($error, $params);
		throw new Exception($error['message'], $error['code']);
	}
}