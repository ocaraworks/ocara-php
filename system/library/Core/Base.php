<?php
/**
 * 应用基类
 * @Copyright (c) http://www.ocara.cn and http://www.ocaraframework.com All rights reserved.
 * @author Lin YiHu <linyhtianwa@163.com>
 */

namespace Ocara\Core;

use \ReflectionException;
use Ocara\Exceptions\Exception;

abstract class Base extends Basis
{
    private $plugin;
    private $eventHandler;
    private $isRegisteredEvent;

    private $events = array();
    private $traits = array();

    private static $classEvents = array();

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
     * @param array $params
     * @return mixed
     * @throws Exception
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
     * @throws Exception
     */
    public static function __callStatic($name, $params)
    {
        if (substr($name, 0, 5) == 'event') {
            $eventName = lcfirst(substr($name, 5));
            $event = ocContainer()->create('event');
            $event->setName($eventName);
            self::$classEvents[self::getClass()][$eventName] = $event;
            return self::$classEvents[self::getClass()][$eventName];
        }
        return ocService()->error->show('no_method', array($name));
    }

    /**
     * 魔术方法获取自定义属性
     * @param string $property
     * @return mixed
     * @throws Exception
     */
    public function __get($property)
    {
        try {
            $result = parent::__get($property);
            return $result;
        } catch (Exception $exception) {
            $reason = $exception->getMessage();
        }

        $reason = !empty($reason) ? $reason : 'Not Found property';

        if (method_exists($this, '__none')) {
            $value = $this->__none($property, $reason);
            return $value;
        }

        ocService()->error->show('no_property', array($property, $reason));
    }

    /**
     * 设置属性
     * @param string $property
     * @param mixed $value
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
     * 获取日志对象
     * @param string $logName
     * @return array|mixed|object|void|null
     * @throws Exception
     */
    public static function log($logName)
    {
        return ocContainer()->create('log', array($logName));
    }

    /**
     * 获取插件
     * @param bool $required
     * @return object|null
     * @throws Exception
     */
    public function plugin($required = true)
    {
        if (is_object($this->plugin)) {
            return $this->plugin;
        }

        if ($required) {
            ocService()->error->show('no_plugin');
        }

        return null;
    }

    /**
     * 设置插件
     * @param object $plugin
     * @return mixed
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
    {
    }

    /**
     * 设置或获取事件
     * @param $eventName
     * @return Event
     * @throws Exception
     */
    public function event($eventName)
    {
        $this->checkRegisteredEvents();

        if (!isset($this->events[$eventName])) {
            $event = ocContainer()->create('event');
            $event->setName($eventName);
            $this->events[$eventName] = $event;
            if ($this->eventHandler && method_exists($this->eventHandler, $eventName)) {
                $event->clear();
                $event->append(array(&$this->eventHandler, $eventName), $eventName);
            }
        }

        return $this->events[$eventName];
    }

    /**
     * 触发事件
     * @param $eventName
     * @param array $params
     * @return array|mixed
     * @throws Exception
     * @throws ReflectionException
     */
    public function fire($eventName, array $params = array())
    {
        $this->checkRegisteredEvents();
        return $this->event($eventName)->trigger($this, $params);
    }

    /**
     * 绑定事件资源包
     * @param $eventHandler
     * @return $this
     */
    public function bindEventHandler($eventHandler)
    {
        $this->checkRegisteredEvents();

        if (is_string($eventHandler) && class_exists($eventHandler)) {
            $eventHandler = new $eventHandler();
        }

        if (is_object($eventHandler)) {
            $this->eventHandler = $eventHandler;
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
            if (array_key_exists(self::getClass(), self::$classEvents)) {
                foreach (self::$classEvents[self::getClass()] as $eventName => $handlers) {
                    $this->events[$eventName] = self::$classEvents[self::getClass()][$eventName];
                }
            }
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