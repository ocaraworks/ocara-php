<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架   服务容器类Container
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara;

defined('OC_PATH') or exit('Forbidden!');

class Container extends Base
{
    /**
     * @var array $_binds 动态绑定类
     * @var array $_singletons 单例绑定类
     * @var array $_instances 已实例化实例
     * @var array $_replaces 要替换的依赖类
     */
    private $_binds = array();
    private $_singletons = array();
    private $_instances = array();
    private $_replaces = array();

    /**
     * 魔术方法（设置未定义属性）
     * @param string $key
     * @param string $value
     * @return Container
     */
    public function __set($key, $value)
    {
        return $this->bind($key, $value);
    }

    /**
     * 魔术方法（获取未定义属性）
     * @param string $key
     * @return mixed
     */
    public function &__get($key)
    {
        $instance = $this->get($key);
        return $instance;
    }

    /**
     * 获取实例
     * @param string $name
     * @param array $params
     * @param array $dependencies
     * @return mixed
     */
    public function get($name, array $params = array(), array $dependencies = array())
    {
        if (!empty($this->_instances[$name])) {
            $instance = $this->_instances[$name];
        } else {
            $instance = $this->create($name, $params);
            $this->_instances[$name] = $instance;
        }

        foreach ($dependencies as $name => $object) {
            $method = 'set' . ucfirst($name);
            if (method_exists($instance, $method)){
                $instance->$method($object);
            }
        }

        return $instance;
    }

    /**
     * 绑定实例
     * @param string $name
     * @param mixed $source
     * @return $this
     */
    public function bind($name, $source)
    {
        if (strstr($name, OC_NS_SEP)) {
            $this->_replaces[$name] = $source;
        } else {
            $this->_binds[$name] = $this->_getMatter($name, $source);
        }
        return $this;
    }

    /**
     * 单例模式绑定实例
     * @param string $name
     * @param mixed $source
     * @return $this
     */
    public function bindSingleton($name, $source)
    {
        $this->_singletons[$name] = $this->_getMatter($name, $source);
        return $this;
    }

    /**
     * 获取绑定信息
     * @param string $name
     * @param mixed $source
     * @return mixed
     * @throws Exception
     */
    protected function _getMatter($name, $source)
    {
        if (!empty($this->_singletons[$name])) {
            Error::show('exists_singleton.');
        }

        return $source;
    }

    /**
     * 是否绑定过
     * @param string $name
     * @return bool
     */
    public function isBound($name)
    {
        if (strstr($name, OC_NS_SEP)) {
            return array_key_exists($name, $this->_replaces);
        }

        return array_key_exists($name, $this->_binds)
            OR array_key_exists($name, $this->_singletons);
    }

    /**
     * 是否存在实例
     * @param string $name
     * @return bool
     */
    public function isInstance($name)
    {
        return array_key_exists($name, $this->_instances);
    }

    /**
     * 新建实例
     * @param string $name
     * @param array $params
     * @return mixed
     * @throws Exception
     */
    public function create($name, array $params = array())
    {
        $source = null;
        $isSingleton = false;

        if (!empty($this->_singletons[$name])) {
            $source = (array)$this->_singletons[$name];
            if (!empty($this->_instances[$name])) {
                return $this->_instances[$name];
            }
            $isSingleton = true;
        } elseif (!empty($this->_binds[$name])) {
            $source = (array)$this->_binds[$name];
        }

        if (empty($source)) {
            Error::show("not_exists_dependence_set");
        }

        if (is_array($source)) {
            $source = array_values($source);
            list($sourceClass, $sourceMethod) = $source;
            if (is_string($sourceClass)) {
                $methodReflection = new \ReflectionMethod($sourceClass, $sourceMethod);
                if (!$methodReflection->isStatic()) {
                    Error::show("invalid_class_static_method", $source);
                }
            }
        }

        $params = (array)$params;
        array_unshift($params, $this);
        $instance = $this->make($source, $params);

        if ($isSingleton) {
            $this->_instances[$name] = $instance;
        }

       return $instance;
    }

    /**
     * 生产实例
     * @param mixed $source
     * @param array $params
     * @return mixed
     * @throws Exception
     */
    public function make($source, array $params = array())
    {
        $type = gettype($source);
        if ($type == 'object' && $source instanceof \Closure
            OR $type == 'array'
            OR $type == 'string' && function_exists($source)
        ) {
            return call_user_func_array($source, $params);
        }

        if ($type == 'object') {
            return $source;
        }

        if ($type == 'string') {
            $reflection = new ReflectionClass($source);
            if (!$reflection->isInstantiable()) {
                Error::show("cannot_instance.");
            }
            $constructor = $reflection->getConstructor();
            if ($constructor === null) {
                $instance = new $source();
            } else {
                $dependencies = $this->getDependencies($constructor->getParameters());
                $instance = $reflection->newInstanceArgs($dependencies);
            }
            return $instance;
        }

        Error::show("invalid_source.");
    }

    /**
     * 获取依赖
     * @param array $params
     * @return array
     * @throws Exception
     */
    public function getDependencies($params)
    {
        $dependencies = array();

        foreach ($params as $object) {
            $dependency = $object->getClass();
            if ($dependency === null) {
                if ($object->isDefaultValueAvailable()) {
                    $class = $object->getDefaultValue();
                }
                Error::show('Invalid_param');
            } else {
                $class = $this->create($dependency->name);
            }
            if ($this->isReplace($class)) {
                $class = $this->_replaces[$class];
            }
            $dependencies[] = $class;
        }

        return $dependencies;
    }
}