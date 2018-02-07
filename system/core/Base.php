<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架    基类Base
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara;

defined('OC_PATH') or exit('Forbidden!');

abstract class Base extends Basis
{
	/**
	 * @var $_error 错误信息
	 * @var $_route 路由信息
	 * @var $_properties 自定义属性
	 */
	protected $_route;
	protected $_plugin;

	/**
	 * 设置路由
	 * @param string|array $route
	 */
	public function setRoute($route)
	{
		$this->_route = Ocara::parseRoute($route);
	}

	/**
	 * 获取当前路由
	 * @param string $name
	 * @return null
	 */
	public function getRoute($name = null)
	{
		if (empty($name)) {
			return $this->_route;
		}
		if (array_key_exists($name, $this->_route)) {
			return $this->_route[$name];
		}

		return null;
	}

	/**
	 * 魔术方法-调用未定义的方法时
	 * @param string $name
	 * @param array $params
	 * @return mixed
	 * @throws Exception
	 */
	public function __call($name, $params)
	{
		$obj = $this;

		while (isset($obj->_plugin) && is_object($obj->_plugin)) {
			if (method_exists($obj->_plugin, $name)) {
				return call_user_func_array(array(&$obj->_plugin, $name), $params);
			} else {
				$obj = $obj->_plugin;
			}
		}

		return parent::__call($name, $params);
	}

	/**
	 * 获取日志对象
	 * @param string $logName
	 */
	public static function log($logName)
	{
		return Ocara::container()->create('log', array($logName));
	}

	/**
	 * 获取插件
	 */
	public function plugin()
	{
		if (property_exists($this, '_plugin') && is_object($this->_plugin)) {
			return $this->_plugin;
		}

		Error::show('no_plugin');
	}
}