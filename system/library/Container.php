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
    private $_registers = array();
    private $_singletons = array();
    private $_instances = array();

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
     * @param $key
     * @return mixed
     */
    public function get($key)
    {
        if (!empty($this->_instances[$key])) {
            $instance = $this->_instances[$key];
        } else {
            $instance = $this->make($key);
        }
        return $instance;
    }

    /**
     * 绑定实例
     * @param $name
     * @param $object
     * @param array $params
     */
    public function bind($name, $source, $params = array())
    {
        $this->_registers[$name] = $this->_getMatter($name, $source, $params);
        return $this;
    }

    /**
     * 单例模式绑定实例
     * @param $name
     * @param $source
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
     * 绑定实例
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
     * 是否存在实例
     * @param $name
     * @return bool
     */
    public function exists($name)
    {
        return array_key_exists($name, $this->_registers)
                OR array_key_exists($name, $this->_singletons);
    }

    /**
     * 生产实例
     * @param $name
     * @return mixed
     * @throws Exception
     */
    public function make($name)
    {
        $matter = array();
        $isSingleton = false;
  
        if (!empty($this->_singletons[$name])) {
            $matter = (array)$this->_singletons[$name];
            $isSingleton = true;
        } elseif (!empty($this->_registers[$name])) {
            $matter = (array)$this->_registers[$name];
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

        $instance = $this->run($source, $params);

        if ($isSingleton) {
            return $this->_instances[$name] = $instance;
        }
        return $instance;
    }

    /**
     * 执行实例
     * @param $object
     * @param $params
     * @return mixed
     * @throws Exception
     */
    public function run($source, $params)
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
                    $param = $object->getDefaultValue();
                }
                Error::show('Invalid_param');
            } else {
                $param = $this->make($dependency->name);
            }
            $dependencies[] = $param;
        }
        return $dependencies;
    }
}