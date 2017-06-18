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
     * @param string $key
     * @param string $value
     */
    public function __set($key, $value)
    {
        return $this->bind($key, $value);
    }

    /**
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
     * @param $name
     * @return mixed
     */
    public function get($name)
    {
        if (!empty($this->_instances[$name])) {
            $instance = $this->_instances[$name];
        } else {
            $instance = $this->_instances[$name] = $this->create($name);
        }
        return $instance;
    }

    /**
     * 绑定实例
     * @param string $name
     * @param mixed $source
     * @param array $params
     */
    public function bind($name, $source, $params = array())
    {
        $this->_binds[$name] = $this->_getMatter($name, $source, $params);
        return $this;
    }

    /**
     * 单例模式绑定实例
     * @param string $name
     * @param mixed $source
     * @param array $params
     * @return $this
     * @throws Exception
     */
    public function bindSingleton($name, $source, $params = array())
    {
        $this->_singletons[$name] = $this->_getMatter($name, $source, $params);
        return $this;
    }

    /**
     * 替换依赖类
     * @param $target
     * @param $replace
     */
    public function replace($target, $replace)
    {
        $this->_replaces[$target] = $replace;
        return $this;
    }

    /**
     * 获取绑定信息
     * @param $name
     * @param $source
     * @param $params
     * @return $this
     */
    protected function _getMatter($name, $source, $params)
    {
        if (!empty($this->_singletons[$name])) {
            Error::show('exists_singleton.');
        }

        $matter[] = $source;
        $matter[] = $params ? (array)$params : array();
        return $matter;
    }

    /**
     * 是否绑定过
     * @param string $name
     * @return bool
     */
    public function isBound($name)
    {
        return array_key_exists($name, $this->_binds)
            OR array_key_exists($name, $this->_singletons);
    }

    /**
     * 是否替换类
     * @param string $class
     * @return bool
     */
    public function isReplace($class)
    {
        return array_key_exists($class, $this->_replaces);
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
     * @param $name
     * @return mixed
     * @throws Exception
     */
    public function create($name)
    {
        $matter = array();
        $isSingleton = false;

        if (!empty($this->_singletons[$name])) {
            $matter = (array)$this->_singletons[$name];
            if (!empty($this->_instances[$name])) {
                return $this->_instances[$name];
            }
            $isSingleton = true;
        } elseif (!empty($this->_binds[$name])) {
            $matter = (array)$this->_binds[$name];
        }

        if (empty($matter)) {
            Error::show("not_exists_dependence_set");
        }

        list($source, $params) = $matter;
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
     * @param $object
     * @param $params
     * @return mixed
     * @throws Exception
     */
    public function make($source, $params)
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
     * @param array $params
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