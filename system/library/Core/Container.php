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
use \ReflectionException;

defined('OC_PATH') or exit('Forbidden!');

class Container extends Basis
{
    /**
     * @var array $binds 动态绑定类
     * @var array $bindSingletons 单例绑定类
     * @var array $instances 类实例
     * @var object $default 默认容器
     */
    public $binds = array();
    public $bindSingletons = array();
    public $instances = array();

    private static $default;

    /**
     * 获取默认容器
     * @return mixed
     */
    public static function getDefault()
    {
        if (self::$default === null) {
            self::$default = new static();
        }
        return self::$default;
    }

    /**
     * 单例模式绑定实例
     * @param $name
     * @param null $source
     * @param array $params
     * @param array $deps
     * @return $this
     * @throws Exception
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

        $matter = $this->getMatterArray($source, $params, $deps);
        if ($matter) {
            $this->bindSingletons[$name] = $matter;
        }

        return $this;
    }

    /**
     * 绑定实例
     * @param $name
     * @param null $source
     * @param array $params
     * @param array $deps
     * @return $this
     * @throws Exception
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

        $matter = $this->getMatterArray($source, $params, $deps);
        if ($matter) {
            $this->binds[$name] = $matter;
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
            if (array_key_exists($name, $this->bindSingletons)) {
                return $this->bindSingletons[$name];
            }
            return array();
        }

        return $this->bindSingletons;
    }

    /**
     * 获取动态绑定
     * @param string $name
     * @return null
     */
    public function getBind($name = null)
    {
        if (func_num_args()) {
            if (array_key_exists($name, $this->binds)) {
                return $this->binds[$name];
            }
            return array();
        }

        return $this->binds;
    }

    /**
     * 获取绑定数组
     * @param $source
     * @param $params
     * @param $deps
     * @return array
     */
    protected function getMatterArray($source, $params, $deps)
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
     * @param null $name
     * @param array $params
     * @param array $deps
     * @return array|mixed|object|自定义属性|null
     * @throws Exception
     * @throws ReflectionException
     */
    public function get($name = null, array $params = array(), array $deps = array())
    {
        if (isset($name)) {
            $instance = null;
            if ($this->hasInstance($name)) {
                $instance = $this->getInstance($name);
            } elseif ($this->hasBindSingleton($name)) {
                $instance = $this->make($name, $params, $deps);
                $this->setInstance($name, $instance);
            } elseif ($this->hasBind($name)){
                $instance = $this->create($name, $params, $deps);
            }
            return $instance;
        }

        return $this->getInstance();
    }

    /**
     * 是否动态和静态绑定过
     * @param string $name
     * @return bool
     */
    public function hasBindAll($name)
    {
        $name = ocClassName($name);
        return array_key_exists($name, $this->binds) || array_key_exists($name, $this->bindSingletons);
    }

    /**
     * 是否静态绑定过
     * @param string $name
     * @return bool
     */
    public function hasBindSingleton($name)
    {
        return array_key_exists(ocClassName($name), $this->bindSingletons);
    }

    /**
     * 是否动态绑定过
     * @param string $name
     * @return bool
     */
    public function hasBind($name)
    {
        return array_key_exists(ocClassName($name), $this->binds);
    }

    /**
     * 是否有绑定或对象
     * @param string $name
     * @return bool
     */
    public function has($name)
    {
        return $this->hasInstance($name) || $this->hasBindAll($name);
    }

    /**
     * 是否存在实例
     * @param $name
     * @return bool
     */
    public function hasInstance($name)
    {
        return array_key_exists($name, $this->instances);
    }

    /**
     * 获取类实例
     * @param mixed $name
     * @return 自定义属性|null
     */
    public function getInstance($name = null)
    {
        if (func_get_args()) {
            if (array_key_exists($name, $this->instances)) {
                return $this->instances[$name];
            }
            return null;
        }
        return $this->instances;
    }

    /**
     * 设置实例
     * @param $name
     * @param $instance
     */
    public function setInstance($name, $instance)
    {
        $this->instances[$name] = $instance;
    }

    /**
     * 新建实例
     * @param $name
     * @param array $params
     * @param array $deps
     * @return array|mixed|object|null
     * @throws Exception
     * @throws ReflectionException
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
     * @param $name
     * @param array $params
     * @param array $deps
     * @return array|mixed|object|null
     * @throws Exception
     * @throws ReflectionException
     */
    public function make($name, array $params = array(), array $deps = array())
    {
        $source = null;

        if (!empty($this->bindSingletons[$name])) {
            if ($this->getInstance($name)) {
                throw new Exception("exists_singleton_object");
            }
            if (is_object($this->bindSingletons[$name])) {
                return $this->setInstance($name, $this->bindSingletons[$name]);
            }
            $matter = (array)$this->bindSingletons[$name];
        } elseif (!empty($this->binds[$name])) {
            if (is_object($this->binds[$name])) {
                return $this->binds[$name];
            }
            $matter = (array)$this->binds[$name];
        } else {
            throw new Exception("not_exists_dependence_set");
        }

        if (empty($matter)) return null;

        $instance = $this->getMatterInstance($matter, $params, $deps);
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
     * @param $params
     * @param $deps
     * @return array|mixed|object|null
     * @throws Exception
     * @throws ReflectionException
     */
    protected function getMatterInstance($matter, $params, $deps)
    {
        list($source, $inputParams, $inputDeps) = $matter;
        $params = array_merge($inputParams, $params);
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
     * @param $args
     * @param $params
     * @return array
     * @throws Exception
     * @throws ReflectionException
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
                } elseif ($this->hasBind($name)) {
                    $class = $this->create($name);
                } elseif ($this->hasBindSingleton($name)){
                    $class = $this->get($name);
                }elseif ($this !== self::$default && self::$default && self::$default->has($name)) {
                    $class = self::$default->create($name);
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
     * @return array|mixed|object|自定义属性|null
     * @throws Exception
     * @throws ReflectionException
     */
    public function __get($property)
    {
        if ($this->hasBindAll($property)) {
            $value = $this->get($property);
            return $value;
        }
    }
}