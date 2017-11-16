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

class Container extends Basis
{
    /**
     * @var array $_binds 动态绑定类
     * @var array $_singletons 单例绑定类
     * @var array $_instances 已实例化实例
     * @var array $_replaces 要替换的依赖类
     * @var object $_default 默认容器
     */
    private $_binds = array();
    private $_singletons = array();
    private $_instances = array();

    protected static $_default;

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
     * @param array $deps
     * @return mixed
     */
    public function get($name, array $params = array(), array $deps = array())
    {
        if (!empty($this->_instances[$name])) {
            $instance = $this->_instances[$name];
        } else {
            $instance = $this->create($name, $params, $deps);
            $this->_instances[$name] = $instance;
        }

        return $instance;
    }

    /**
     * 设置默认容器
     * @param Container $container
     */
    public static function setDefault(Container $container)
    {
        self::$_default = $container;
    }

    /**
     * 获取默认容器
     * @return mixed
     */
    public static function getDefault()
    {
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
                Error::show('exists_container_singleton');
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
     * 是否绑定过
     * @param string $name
     * @return bool
     */
    public function has($name)
    {
        if (strstr($name, OC_NS_SEP)) {
            $name = OC_NS_SEP . ltrim($name, OC_NS_SEP);
        }

        return array_key_exists($name, $this->_binds)
            OR array_key_exists($name, $this->_singletons);
    }

    /**
     * 是否存在实例
     * @param string $name
     * @return bool
     */
    public function hasInstance($name)
    {
        return array_key_exists($name, $this->_instances);
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
            if (!empty($this->_instances[$name])) {
                return $this->_instances[$name];
            }
            $matter = (array)$this->_singletons[$name];
            $isSingleton = true;
        } elseif (!empty($this->_binds[$name])) {
            $matter = (array)$this->_binds[$name];
        }

        if (empty($matter)) {
            Error::show("not_exists_dependence_set");
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
                    Error::show("invalid_class_static_method", $source);
                }
            }
        }

        $instance = $this->make($source, $params, $deps);
        if ($isSingleton) {
            $this->_instances[$name] = $instance;
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
                    Error::show("cannot_instance.");
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

        Error::show("invalid_source.");
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
                        Error::show('fault_method_param');
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
}