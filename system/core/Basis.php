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

abstract class Basis
{
	/**
	 * @var $_properties 自定义属性
	 */
	private $_properties = array();
	protected $_event;

	protected $_events = array();

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
	 * 清理自定义属性
	 */
	public function clearProperty()
	{
		$this->_properties = array();
	}

	/**
	 * 设置或获取事件
	 * @param $eventName
	 * @return mixed
	 */
	public function event($eventName)
	{
		if (!isset($this->_events[$eventName])) {
			$event = Ocara::container()->create('event', array($eventName));
			$this->_events[$eventName] = $event;
			if ($this->_event && method_exists($this->_event, $eventName)) {
				$event->clear();
				$event->append(array(&$this->_event, $eventName), $eventName);
			}
		}

		return $this->_events[$eventName];
	}

	/**
	 * 绑定事件资源包
	 * @param $eventObject
	 * @return $this
	 */
	public function bindEvents($eventObject)
	{
		$this->_event = $eventObject;
		return $this;
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
	 * 魔术方法-调用未定义的方法时
	 * @param string $name
	 * @param array $params
	 * @return mixed
	 * @throws Exception
	 */
	public function __call($name, $params)
	{
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
}