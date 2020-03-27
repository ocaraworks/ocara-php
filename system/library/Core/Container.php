<?php
/**
 * 服务容器类
 * @Copyright (c) http://www.ocara.cn and http://www.ocaraframework.com All rights reserved.
 * @author Lin YiHu <linyhtianwa@163.com>
 */

namespace Ocara\Core;

use \ReflectionClass;
use \ReflectionMethod;
use Ocara\Exceptions\Exception;

/**
 * Class ServiceProvider
 * @property Application $app
 * @property Config $config
 * @property Lang $lang
 * @property ExceptionHandler $exceptionHandler
 */
class Container extends Basis
{
    /**
     * @var array $binds 动态绑定类
     * @var array $bindSingletons 单例绑定类
     * @var array $instances 类实例
     */
    public $binds = array();
    public $bindSingletons = array();
    public $instances = array();

    /**
     * 单例模式绑定实例
     * @param $name
     * @param mixed $source
     * @param array $params
     * @param array $deps
     * @return $this
     * @throws Exception
     */
    public function bindSingleton($name, $source = null, array $params = array(), array $deps = array())
    {
        if (is_array($name)) {
            foreach ($name as $row) {
                call_user_func_array(array($this, __METHOD__), $row);
            }
        } else {
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
                if (is_string($source)) {
                    $source = ocClassName($source);
                    if ($source != $name) {
                        $this->bindSingleton($source, $source, $params, $deps);
                    }
                }
            }
        }

        return $this;
    }

    /**
     * 绑定实例
     * @param $name
     * @param mixed $source
     * @param array $params
     * @param array $deps
     * @return $this
     * @throws Exception
     */
    public function bind($name, $source = null, array $params = array(), array $deps = array())
    {
        if (is_array($name)) {
            foreach ($name as $row) {
                call_user_func_array(array($this, __METHOD__), $row);
            }
        } else {
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
                if (is_string($source)) {
                    $source = ocClassName($source);
                    if ($source != $name) {
                        $this->bind($source, $source, $params, $deps);
                    }
                }
            }
        }

        return $this;
    }

    /**
     * 获取静态绑定
     * @param null $name
     * @return array|mixed
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
     * @return array|mixed
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
     * @param mixed $source
     * @param array $params
     * @param array $deps
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
     * @param string $name
     * @param array $params
     * @param array $deps
     * @return array|mixed|object|void|null
     * @throws Exception
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
            } elseif ($this->hasBind($name)) {
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
     * @param string $name
     * @return bool
     */
    public function hasInstance($name)
    {
        return array_key_exists($name, $this->instances);
    }

    /**
     * 获取类实例
     * @param string $name
     * @return array|mixed|null
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
     * @param string $name
     * @param $instance
     */
    public function setInstance($name, $instance)
    {
        $this->instances[$name] = $instance;
    }

    /**
     * 新建实例
     * @param string $name
     * @param array $params
     * @param array $deps
     * @return array|mixed|object|void|null
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
     * @return array|mixed|object|void|null
     * @throws Exception
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
                if (method_exists($instance, $method)) {
                    $instance->$method($object);
                }
            }
            return $instance;
        }

        throw new Exception('invalid_source');
    }

    /**
     * 获取对象实例
     * @param array $matter
     * @param array $params
     * @param array $deps
     * @return array|mixed|object|null
     * @throws Exception
     */
    protected function getMatterInstance(array $matter, array $params, array $deps)
    {
        list($source, $inputParams, $inputDeps) = $matter;
        $params = array_merge($inputParams, $params);
        $instance = null;

        if (is_array($source)) {
            $source = array_values($source);
            list($sourceClass, $sourceMethod) = $source;
            if (is_string($sourceClass)) {
                try {
                    $methodReflection = new ReflectionMethod($sourceClass, $sourceMethod);
                    $isStatic = $methodReflection->isStatic();
                } catch (\Exception $exception) {
                    throw new Exception($exception->getMessage(), $exception->getCode());
                }
                if (!$isStatic) {
                    throw new Exception("invalid_class_static_method");
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
                try {
                    $reflection = new ReflectionClass($source);
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
                } catch (\Exception $exception) {
                    throw new Exception($exception->getMessage(), $exception->getCode());
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
    public function getDependencies(array $args, array $params)
    {
        $class = null;
        $container = function_exists('ocContainer') ? ocContainer() : null;
        $dependencies = array();

        foreach ($args as $key => $object) {
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
                $name = ocClassName($dependency->name);
                if (isset($params[$key]) && is_object($params[$key])) {
                    $class = $params[$key];
                } elseif ($this->hasBind($name)) {
                    $class = $this->create($name);
                } elseif ($this->hasBindSingleton($name)) {
                    $class = $this->get($name);
                } elseif (is_object($container)
                    && $container instanceof Container
                    && $this !== $container
                    && $container->has($name)
                ) {
                    $class = $container->create($name);
                }
            }
            if ($class) {
                $dependencies[] = $class;
            }
        }

        return $dependencies;
    }

    /**
     * 获取无法访问的属性
     * @param string $property
     * @return array|mixed|object|void|null
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