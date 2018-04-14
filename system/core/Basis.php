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
	protected $_event;

	protected $_events = array();
	protected $_properties = array();
	protected $_traits = array();

	/**
	 * 实例化
	 * @param mixed $params
	 * @return static
	 */
	public static function build($params = null)
	{
		return call_user_func_array('ocClass', array(self::getClass(), func_get_args()));
	}

    /**
     * 获取自定义属性
     * @param string $name
     * @param mixed $args
     * @return array|mixed
     */
	public function &getProperty($name = null, $args = null)
	{
		if (func_num_args()) {
			if (array_key_exists($name, $this->_properties)) {
				return $this->_properties[$name];
			}
            if (method_exists($this, '__none')) {
                $this->__none($name);
            } else {
                Error::show('no_property', array($name));
            }
		}

		return $this->_properties;
	}

	/**
	 * 设置自定义属性
	 * @param mixed $name
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
     * 删除自定义属性
     * @param mixed $name
     */
    public function delProperty($name)
    {
        if (is_array($name)) {
            array_unshift($this->_properties, $name);
            call_user_func_array('ocDel', $name);
        } else {
            ocDel($this->_properties, $name);
        }
    }

    /**
     * 清理自定义属性
     * @param null $args
     */
	public function clearProperties($args = null)
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
			$event = Ocara::container()->create('event');
			$event->setName($eventName);
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
		if (is_string($eventObject) && class_exists($eventObject)) {
			$eventObject = new $eventObject();
		}

		if (is_object($eventObject)) {
			$this->_event = $eventObject;
		}

		return $this;
	}

	/**
	 * 动态行为扩展
	 * @param string|object $name
	 * @param $function
	 */
	public function traits($name, $function = null)
	{
		if (is_string($name)) {
			$this->_traits[$name] = $function;
		} elseif (is_object($name)) {
			if (is_array($function)) {
				foreach ($function as $name => $value) {
					$setMethod = 'set' . ucfirst($name);
					$name->$setMethod($value);
				}
			}
			$methods = get_class_methods($name);
			foreach ($methods as $method) {
				$this->traits($method, array($name, $method));
			}
		}
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
		if (isset($this->_traits[$name])) {
			call_user_func_array($this->_traits[$name], $params);
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
		if ($this->has($key)) {
			return $this->get($key);
		}

		if (method_exists($this, '__none')) {
			$this->__none($key);
		} else {
			Error::show('no_property', array($key));
		}
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