<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架   服务容器类Container
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara\Core;

use Ocara\Core\Basis;
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
     * 单例模式绑定实例
     * @param string $name
     * @param mixed $source
     * @param array $params
     * @param array $deps
     * @return $this
     */
    public function bindSingleton($name, $source = null, array $params = array(), array $deps = array())
    {
        $name = ocClassName($name);
        if ($this->hasBind($name)) {
            throw new Exception('exists_bind_class');
        }

        if (empty($source)) {
            $source = $name;
        }

        $matter = $this->_getMatterArray($source, $params, $deps);
        if ($matter) {
            $this->_singletons[$name] = $matter;
        }

        return $this;
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
        $name = ocClassName($name);
        if ($this->hasBindSingleton($name)) {
            throw new Exception('exists_singleton_bind_class');
        }

        if (empty($source)) {
            $source = $name;
        }

        $matter = $this->_getMatterArray($source, $params, $deps);
        if ($matter) {
            $this->_binds[$name] = $matter;
        }

        return $this;
    }

    /**
     * 获取静态绑定
     * @param string $name
     * @return null
     */
    public function getBindSingleton($name = null)
    {
        if (func_num_args()) {
            if (array_key_exists($name, $this->_singletons)) {
                return $this->_singletons[$name];
            }
            return array();
        }

        return $this->_binds;
    }

    /**
     * 获取动态绑定
     * @param string $name
     * @return null
     */
    public function getBind($name = null)
    {
        if (func_num_args()) {
            if (array_key_exists($name, $this->_binds)) {
                return $this->_binds[$name];
            }
            return array();
        }

        return $this->_binds;
    }

    /**
     * 获取绑定数组
     * @param string $name
     * @param mixed $source
     * @param array $params
     * @param array $deps
     * @return mixed
     * @throws Exception
     */
    protected function _getMatterArray($source, $params, $deps)
    {
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
            } elseif ($this->hasBindSingleton($name)) {
                $this->_properties[$name] = $this->make($name, $params, $deps);
                $instance = $this->_properties[$name];
            } elseif ($this->hasBind($name)){
                $instance = $this->create($name, $params, $deps);
            }
            return $instance;
        }

        return $this->_properties;
    }

    /**
     * 是否动态和静态绑定过
     * @param string $name
     * @return bool
     */
    public function hasBindAll($name)
    {
        $name = ocClassName($name);
        return array_key_exists($name, $this->_binds) || array_key_exists($name, $this->_singletons);
    }

    /**
     * 是否静态绑定过
     * @param string $name
     * @return bool
     */
    public function hasBindSingleton($name)
    {
        return array_key_exists(ocClassName($name), $this->_singletons);
    }

    /**
     * 是否动态绑定过
     * @param string $name
     * @return bool
     */
    public function hasBind($name)
    {
        return array_key_exists(ocClassName($name), $this->_binds);
    }

    /**
     * 是否有绑定或对象
     * @param string $name
     * @return bool
     */
    public function has($name)
    {
        return $this->hasBindAll($name) || $this->has($name);
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
        if ($this->hasBindSingleton($name)) {
            throw new Exception('exists_singleton_bind_class');
        }
        if ($this->hasBind($name)) {
            return $this->make($name, $params, $deps);
        } else {
            return null;
        }
    }

    /**
     * 生产实例
     * @param string $name
     * @param array $params
     * @param array $deps
     * @return mixed
     * @throws Exception
     */
    public function make($name, array $params = array(), array $deps = array())
    {
        $source = null;
        $isSingleton = false;

        $matter = array();
        if (!empty($this->_singletons[$name])) {
            if (!empty($this->_properties[$name])) {
                throw new Exception("exists_singleton_object");
            }
            if (is_object($this->_singletons[$name])) {
                return $this->_properties[$name] = $this->_singletons[$name];
            }
            $isSingleton = true;
            $matter = (array)$this->_singletons[$name];
        } elseif (!empty($this->_binds[$name])) {
            if (is_object($this->_binds[$name])) {
                return $this->_binds[$name];
            }
            $matter = (array)$this->_binds[$name];
        } else {
            throw new Exception("not_exists_dependence_set");
        }

        if (empty($matter)) return null;

        $instance = $this->_getMatterInstance($matter, $params, $deps);
        if ($instance) {
            foreach ($deps as $name => $object) {
                $method = 'set' . ucfirst($name);
                if (method_exists($instance, $method)){
                    $instance->$method($object);
                }
            }
            return $instance;
        }

        throw new Exception('invalid_source');
    }

    /**
     * 获取对象实例
     * @param $matter
     * @throws Exception
     * @throws \ReflectionException
     */
    protected function _getMatterInstance($matter, $params, $deps)
    {
        list($source, $inputParams, $inputDeps) = $matter;
        $params = array_merge($inputParams, $params);
        $deps = array_merge($inputDeps, $deps);
        $instance = null;

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

        return $instance;
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
        if ($this->hasBindAll($property)) {
            $value = $this->get($property);
            return $value;
        }
    }
}