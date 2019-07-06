<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架    基类Base
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara\Core;

use Ocara\Exceptions\Exception;

defined('OC_PATH') or exit('Forbidden!');

abstract class Base extends Basis
{
	/**
	 * @var $route 路由信息
	 */
    private $plugin;
    private $event;

    private $events = array();
    private $traits = array();

    private $isRegisteredEvent;

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

		while (isset($obj->plugin) && is_object($obj->plugin)) {
			if (method_exists($obj->plugin, $name)) {
				return call_user_func_array(array(&$obj->plugin, $name), $params);
			} else {
				$obj = $obj->plugin;
			}
		}

        if (isset($this->traits[$name])) {
            return call_user_func_array($this->traits[$name], $params);
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
        try {
            $result = parent::__get($property);
            return $result;
        } catch (Exception $exception) {
            $reason = $exception->getMessage();
        }

        $reason = !empty($reason) ? : 'Not Found property';

        if (method_exists($this, '__none')) {
            $value = $this->__none($property, $reason);
            return $value;
        }

        ocService()->error->show('no_property', array($property, $reason));
    }

    /**
     * 设置属性
     * @param $property
     * @param null $value
     */
    protected function setProperty($property, $value = null)
    {
        if (is_array($property)) {
            foreach ($property as $name => $value) {
                $this->$name = $value;
            }
        } else {
            $this->$property = $value;
        }
    }

    /**
     * 清理属性
     * @param array $fields
     */
    protected function clearProperties(array $fields = array())
    {
        $fields = $fields ? : array_keys($this->toArray());

        foreach ($fields as $field) {
            if (isset($this->$field)) {
                $this->$field = null;
            }
        }

        $this->properties = array();
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
	public function plugin($required = true)
	{
		if (is_object($this->plugin)) {
			return $this->plugin;
		}

		if ($required) {
		    throw new Exception('no_plugin');
        }
	}

    /**
     * 设置插件
     * @param $plugin
     */
    public function setPlugin($plugin)
    {
        $this->plugin = $plugin;
        return $this->plugin;
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
        $this->checkRegisteredEvents();

        if (!isset($this->events[$eventName])) {
            $event = ocContainer()->create('event');
            $event->setName($eventName);
            $this->events[$eventName] = $event;
            if ($this->event && method_exists($this->event, $eventName)) {
                $event->clear();
                $event->append(array(&$this->event, $eventName), $eventName);
            }
        }

        return $this->events[$eventName];
    }

    /**
     * 触发事件
     * @param string $eventName
     * @param array $params
     * @return mixed
     */
    public function fire($eventName, array $params = array())
    {
        $this->checkRegisteredEvents();
        return $this->event($eventName)->trigger($this, $params);
    }

    /**
     * 绑定事件资源包
     * @param $eventObject
     * @return $this
     */
    public function bindEvents($eventObject)
    {
        $this->checkRegisteredEvents();

        if (is_string($eventObject) && class_exists($eventObject)) {
            $eventObject = new $eventObject();
        }

        if (is_object($eventObject)) {
            $this->event = $eventObject;
        }

        return $this;
    }

    /**
     * 检测事件注册
     */
    protected function checkRegisteredEvents()
    {
        if (empty($this->isRegisteredEvent)) {
            $this->isRegisteredEvent = true;
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
            $this->traits[$name] = $function;
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