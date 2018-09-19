<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架   服务容器类Container
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara;

use Ocara\Basis;
use Ocara\Exceptions\Exception;

defined('OC_PATH') or exit('Forbidden!');

class Container extends Basis
{
    /**
     * @var array $_binds 动态绑定类
     * @var array $_singletons 单例绑定类
     * @var object $_default 默认容器
     */
    public $_binds = array();
    public $_singletons = array();

    private static $_default;

    /**
     * 设置默认容器
     * @param Container $container
     */
    public static function setDefault(Container $container)
    {
        if (self::$_default === null) {
            self::$_default = $container;
        }
    }

    /**
     * 获取默认容器
     * @return mixed
     */
    public static function getDefault()
    {
        if (self::$_default === null) {
            self::$_default = new static();
        }
        return self::$_default;
    }

    /**
     * 绑定实例
     * @param string $name
     * @param mixed $source
     * @param array $params
     * @param array $deps
     * @return $this
     */
    public function bind($name, $source = null, array $params = array(), array $deps = array())
    {
        if (strstr($name, OC_NS_SEP)) {
            $name = OC_NS_SEP . ltrim($name, OC_NS_SEP);
        }

        if (!$source) {
            $source = $name;
        }

        $matter = $this->_getMatter($name, $source, $params, $deps);
        if ($matter) {
            $this->_binds[$name] = $matter;
        }

        return $this;
    }

    /**
     * 单例模式绑定实例
     * @param string $name
     * @param mixed $source
     * @param array $params
     * @param array $deps
     * @return $this
     */
    public function bindSingleton($name, $source = null, array $params = array(), array $deps = array())
    {
        if (strstr($name, OC_NS_SEP)) {
            $name = OC_NS_SEP . ltrim($name, OC_NS_SEP);
        }

        if (!$source) {
            $source = $name;
        }

        $matter = $this->_getMatter($name, $source, $params, $deps, true);
        if ($matter) {
            $this->_singletons[$name] = $matter;
        }

        return $this;
    }

    /**
     * 获取绑定信息
     * @param string $name
     * @return null
     */
    public function getBind($name)
    {
        if (array_key_exists($name, $this->_binds)) {
            return $this->_binds[$name][0];
        }

        if (array_key_exists($name, $this->_singletons)) {
            return $this->_singletons[$name][0];
        }

        return null;
    }

    /**
     * 获取所有绑定数扰
     * @return array
     */
    public function getAllBind()
    {
        return $this->_binds;
    }

    /**
     * 获取绑定参数
     * @param string $name
     * @return null
     */
    public function getBindParams($name)
    {
        if (array_key_exists($name, $this->_binds)) {
            return $this->_binds[$name][1];
        }

        if (array_key_exists($name, $this->_singletons)) {
            return $this->_singletons[$name][1];
        }

        return array();
    }

    /**
     * 获取绑定信息
     * @param string $name
     * @param mixed $source
     * @param array $params
     * @param array $deps
     * @param bool $singleton
     * @return mixed
     * @throws Exception
     */
    protected function _getMatter($name, $source, $params, $deps, $singleton = false)
    {
        if (!empty($this->_singletons[$name])) {
            if (!$singleton) {
                throw new Exception('exists_container_singleton');
            }
            return array();
        }

        $matter = array(
            $source,
            $params ? (array)$params : array(),
            $deps ? (array)$deps : array()
        );

        return $matter;
    }

    /**
     * 获取实例
     * @param string $name
     * @param array $params
     * @param array $deps
     * @return array|mixed
     */
    public function get($name = null, array $params = array(), array $deps = array())
    {
        if (isset($name)) {
            $instance = null;
            if ($this->hasProperty($name)) {
                $instance = $this->_properties[$name];
            } elseif ($this->hasBind($name)) {
                $this->_properties[$name] = $this->create($name, $params, $deps);
                $instance = $this->_properties[$name];
            }
            return $instance;
        }

        return $this->_properties;
    }

    /**
     * 是否绑定过
     * @param string $name
     * @return bool
     */
    public function hasBind($name)
    {
        if (strstr($name, OC_NS_SEP)) {
            $name = OC_NS_SEP . ltrim($name, OC_NS_SEP);
        }

        return array_key_exists($name, $this->_binds) || array_key_exists($name, $this->_singletons);
    }

    /**
     * 是否有绑定或对象
     * @param string $name
     * @return bool
     */
    public function has($name)
    {
        return $this->hasBind($name) || $this->has($name);
    }

    /**
     * 新建实例
     * @param string $name
     * @param array $params
     * @param array $deps
     * @return mixed
     * @throws Exception
     */
    public function create($name, array $params = array(), array $deps = array())
    {
        $source = null;
        $isSingleton = false;

        $matter = array();
        if (!empty($this->_singletons[$name])) {
            if (!empty($this->_properties[$name])) {
                return $this->_properties[$name];
            }
            $matter = (array)$this->_singletons[$name];
            $isSingleton = true;
        } elseif (!empty($this->_binds[$name])) {
            $matter = (array)$this->_binds[$name];
        }

        if (empty($matter)) {
            throw new Exception("not_exists_dependence_set");
        }

        list($source, $inputParams, $inputDeps) = $matter;
        $params = array_merge($inputParams, $params);
        $deps = array_merge($inputDeps, $deps);

        if (is_array($source)) {
            $source = array_values($source);
            list($sourceClass, $sourceMethod) = $source;
            if (is_string($sourceClass)) {
                $methodReflection = new \ReflectionMethod($sourceClass, $sourceMethod);
                if (!$methodReflection->isStatic()) {
                    throw new Exception("invalid_class_static_method", $source);
                }
            }
        }

        $instance = $this->make($source, $params, $deps);
        if ($isSingleton) {
            $this->_properties[$name] = $instance;
        }

        return $instance;
    }

    /**
     * 生产实例
     * @param mixed $source
     * @param array $params
     * @param array $deps
     * @return mixed
     * @throws Exception
     */
    public function make($source, array $params = array(), array $deps = array())
    {
        $type = gettype($source);
        if ($type == 'array') {
            $instance = call_user_func_array($source, $params);
        } elseif ($type == 'object') {
            if ($source instanceof \Closure) {
                $instance = call_user_func_array($source, $params);
            } else {
                $instance = $source;
            }
        } elseif ($type == 'string') {
            if (function_exists($source)) {
                $instance = call_user_func_array($source, $params);
            } else {
                $reflection = new \ReflectionClass($source);
                if (!$reflection->isInstantiable()) {
                    throw new Exception("cannot_instance.");
                }
                $constructor = $reflection->getConstructor();
                if ($constructor === null) {
                    $instance = new $source();
                } else {
                    $dependencies = $this->getDependencies($constructor->getParameters(), $params);
                    $instance = $reflection->newInstanceArgs($dependencies);
                }
            }
        }

        if ($instance) {
            foreach ($deps as $name => $object) {
                $method = 'set' . ucfirst($name);
                if (method_exists($instance, $method)){
                    $instance->$method($object);
                }
            }
            return $instance;
        }

        throw new Exception("invalid_source.");
    }

    /**
     * 获取依赖
     * @param array $args
     * @param array $params
     * @return array
     * @throws Exception
     */
    public function getDependencies($args, $params)
    {
        $dependencies = array();

        foreach ($args as $key => $object) {
            $class = null;
            $dependency = $object->getClass();
            if ($dependency === null) {
                if (isset($params[$key])) {
                    $class = $params[$key];
                } else {
                    if ($object->isDefaultValueAvailable()) {
                        $class = $object->getDefaultValue();
                    } else {
                        throw new Exception('fault_method_param');
                    }
                }
            } else {
                $name = OC_NS_SEP . $dependency->name;
                if (isset($params[$key]) && is_object($params[$key])) {
                    $class = $params[$key];
                } elseif ($this->has($name)) {
                    $class = $this->create($name);
                } elseif ($this !== self::$_default && self::$_default && self::$_default->has($name)) {
                    $class = self::$_default->create($name);
                }
            }
            if ($class) {
                $dependencies[] = $class;
            }
        }

        return $dependencies;
    }

    /**
     * 魔术方法-获取自定义属性
     * @param string $property
     * @return mixed
     * @throws Exception
     */
    public function __get($property)
    {
        if ($this->hasBind($property)) {
            $value = $this->get($property);
            return $value;
        }
    }
}