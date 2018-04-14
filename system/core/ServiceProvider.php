<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架 服务提供器类Provider
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara;

use Ocara\Interfaces\ServiceProvider as ServiceProviderInterface;

class ServiceProvider extends DataSource implements ServiceProviderInterface
{
    protected $_container;

    /**
     * 初始化
     * ServiceProvider constructor.
     * @param array $data
     */
    public function __construct(array $data = array())
    {
        $this->_container = new Container();
        $this->set($data);
        $this->register();
    }

    /**
     * 注册服务组件
     * @param array $data
     */
    public function register()
    {}

    /**
     * 获取容器
     */
    public function container()
    {
        return $this->_container;
    }

    /**
     * 设置容器
     * @param $container
     */
    public function setContainer($container)
    {
        $this->_container = $container;
    }

    /**
     * 检测组件是否存在
     * @param $name
     * @return bool
     */
    public function has($name)
    {
        return array_key_exists($name, $this->_properties)
            || $this->_container->has($name)
            || Ocara::container()->has($name);
    }

    /**
     * 获取服务组件（方法重写）
     * @param string $name
     * @param null $args
     * @return mixed
     */
    public function get($name = null, $args = null)
    {
        if (func_num_args()) {
            $instance = null;
            if (array_key_exists($name, $this->_properties)) {
                $instance = $this->_properties[$name];
            } elseif ($this->has($name)) {
                $args = func_get_args();
                $params = array_key_exists(1, $args) ? (array)$args[1] : array();
                $deps = array_key_exists(2, $args) ? (array)$args[2] : array();
                $instance = $this->createService($name, $params, $deps);
                if ($instance) {
                    $this->set($name, $instance);
                }
            }
            return $instance;
        }

        return $this->_properties;
    }

    /**
     * 新建服务组件
     * @param mixed $key
     * @param array $params
     * @param array $deps
     * @return mixed|null
     */
    public function createService($key, $params = array(), $deps = array())
    {
        $instance = null;

        if ($this->_container && $this->_container->has($key)) {
            $instance = $this->_container->create($key, $params, $deps);
        } elseif (Ocara::container()->has($key)) {
            $instance = Ocara::container()->create($key, $params, $deps);
        }

        return $instance;
    }

    /**
     * 属性不存在时的处理
     * @param string $key
     * @throws Exception\Exception
     */
    public function __none($key)
    {
        Error::show('no_service', array($key));
    }
}