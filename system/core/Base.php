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
	protected $_eventHandler;

	protected $_events = array();

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
	 * 获取事件
	 * @param $eventName
	 * @return mixed
	 */
	public function event($eventName)
	{
		if (!isset($this->_events[$eventName])) {
			$this->_events[$eventName] = new $this->_eventHandler();
		}

		return $this->_events[$eventName];
	}

	/**
	 * 设置事件管理器
	 * @param $class
	 */
	public function setEventHandler($class)
	{
		if (class_exists($class)) {
			return $this->_eventHandler = $class;
		}

		Error::show('not_exists_class', $class);
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