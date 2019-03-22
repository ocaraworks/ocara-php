<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架    基类Base
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara\Core;

use Ocara\Core\Basis;
use Ocara\Core\Container;

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
    protected $_event;

    protected $_events = array();
    protected $_traits = array();

    private $_isRegisteredEvent;

    /**
     * 实例化
     * @param mixed $params
     * @return static
     */
    public static function build($params = null)
    {
        $params = array(self::getClass(), func_get_args());
        return call_user_func_array('ocClass', $params);
    }

    /**
     * 魔术方法-调用未定义的方法时
     * @param string $name
     * @param $params
     * @return mixed
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

        if (isset($this->_traits[$name])) {
            return call_user_func_array($this->_traits[$name], $params);
        }

        ocService()->error->show('no_method', array($name));
	}

    /**
     * 魔术方法-调用未定义的静态方法时
     * @param string $name
     * @param array $params
     * @return mixed
     */
    public static function __callStatic($name, $params)
    {
        return ocService()->error->show('no_method', array($name));
    }

    /**
     * 魔术方法-获取自定义属性
     * @param string $property
     * @return array|mixed|自定义属性
     */
    public function __get($property)
    {
        //get the property which is in the $_properties.
        if (!property_exists($this, $property)) {
            if (array_key_exists($property, $this->_properties)) {
                $value = $this->_properties[$property];
                return $value;
            }
        }

        if (method_exists($this, '__none')) {
            $value = $this->__none($property);
            return $value;
        }

        ocService()->error->show('no_property', array($property));
    }

    /**
     * 获取日志对象
     * @param $logName
     * @return mixed
     */
	public static function log($logName)
	{
		return ocContainer()->create('log', array($logName));
	}

	/**
	 * 获取插件
	 */
	public function plugin()
	{
		if (property_exists($this, '_plugin') && is_object($this->_plugin)) {
			return $this->_plugin;
		}

        ocService()->error->show('no_plugin');
	}

    /**
     * 事件注册
     */
	public function registerEvents()
    {}

    /**
     * 设置或获取事件
     * @param $eventName
     * @return mixed
     */
    public function event($eventName)
    {
        $this->_checkRegisteredEvents();

        if (!isset($this->_events[$eventName])) {
            $event = ocContainer()->create('event');
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
     * 触发事件
     * @param string $eventName
     * @param array $params
     * @return mixed
     */
    public function fire($eventName, array $params = array())
    {
        $this->_checkRegisteredEvents();
        return $this->event($eventName)->trigger($this, $params);
    }

    /**
     * 绑定事件资源包
     * @param $eventObject
     * @return $this
     */
    public function bindEvents($eventObject)
    {
        $this->_checkRegisteredEvents();

        if (is_string($eventObject) && class_exists($eventObject)) {
            $eventObject = new $eventObject();
        }

        if (is_object($eventObject)) {
            $this->_event = $eventObject;
        }

        return $this;
    }

    /**
     * 检测事件注册
     */
    protected function _checkRegisteredEvents()
    {
        if (empty($this->_isRegisteredEvent)) {
            $this->_isRegisteredEvent = true;
            $this->registerEvents();
        }
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
}