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

abstract class Base
{
	/**
	 * @var $_error 错误信息
	 * @var $_route 路由信息
	 * @var $_properties 自定义属性
	 */
	private $_route;
	private $_properties = array();

	public static $container;

	/**
	 * 实例化
	 * @return static
	 */
	public static function build()
	{
		return new static();
	}

	/**
	 * 获取自定义属性
	 * @param string $name
	 * @return array
	 */
	public function &getProperty($name = null)
	{
		if (func_num_args()) {
			if ($this->hasProperty($name)) {
				return $this->_properties[$name];
			}
			return $this->$name;
		}
		return $this->_properties;
	}

	/**
	 * 设置自定义属性
	 * @param string $name
	 * @param mixed $value
	 */
	public function setProperty($name, $value = null)
	{
		if (is_array($name)) {
			$this->_properties = array_merge($this->_properties, $name);
		} else {
			$this->_properties[$name] = $value;
		}
	}

	/**
	 * 设置自定义属性
	 * @param string $name
	 * @return bool
	 */
	public function hasProperty($name)
	{
		return array_key_exists($name, $this->_properties);
	}

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
	 * 返回当前类名（去除命名空间）
	 * @return string
	 */
	public static function getClassName()
	{
		$class = get_called_class();
		return substr($class, strrpos($class, OC_NS_SEP) + 1);
	}

	/**
	 * 返回当前类名（含命名空间）
	 * @return string
	 */
	public static function getClass()
	{
		return OC_NS_SEP . get_called_class();
	}

	/**
	 * 清理自定义属性
	 */
	public function clearProperty()
	{
		$this->_properties = array();
	}

	/**
	 * 写全局日志
	 * @param string $content
	 * @param string $logName
	 * @param string $type
	 * @param bool $traceLog
	 */
	public static function globalLog($content, $logName = null, $type = 'info', $traceLog = false)
	{
		if (empty($logName)) {
			$logName = 'common';
		}

		$time        = microtime(true);
		$traceString = null;
		$traceInfo   = array();
		$type 		 = $type ? $type : 'info';

		if($traceLog) {
			$traceInfo = debug_backtrace();
			$traceString = GlobalLog::getTraceString($traceInfo);
		}

		$params = array($logName, $time, $content, $traceString, $traceInfo);
		if ($callback = ocConfig('CALLBACK.global_log', OC_EMPTY)) {
			array_unshift($params, $type);
			Call::run($callback, $params);
		} else {
			Call::run(array(GlobalLog::getInstance(), $type), $params);
		}
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
		
		Error::show('no_method', array($name));
	}

	/**
	 * 魔术方法-调用未定义的静态方法时
	 * >= php 5.3
	 * @param string $name
	 * @param array $params
	 * @throws Exception
	 */
	public static function __callStatic($name, $params)
	{
		Error::show('no_method', array($name));
	}

	/**
	 * 魔术方法-检测属性是否存在
	 * @param string $property
	 * @return bool
	 */
	public function __isset($property)
	{
		return array_key_exists($property, $this->_properties);
	}

	/**
	 * 魔术方法-获取自定义属性
	 * @param string $key
	 * @return mixed
	 * @throws Exception
	 */
	public function &__get($key)
	{
		if (array_key_exists($key, $this->_properties)) {
			return $this->_properties[$key];
		}

		Error::show('no_property', array($key));
	}

	/**
	 * 魔术方法-设置自定义属性
	 * @param string $key
	 * @param mxied $value
	 * @return mixed
	 */
	public function __set($key, $value)
	{
		return $this->_properties[$key] = $value;
	}

	/**
	 * 魔术方法-删除属性
	 * @param string $property
	 */
	public function __unset($property)
	{
		$this->_properties[$property] = null;
		unset($this->_properties[$property]);
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