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

class ServiceProvider extends Base implements ServiceProviderInterface
{
    protected $_services;
    protected $_container;

    /**
     * 初始化
     * ServiceProvider constructor.
     * @param array $data
     */
    public function __construct($data = array())
    {
        $this->_container = new Container();
        $this->register($data);
    }

    /**
     * 注册服务组件
     * @param array $data
     */
    public function register($data = array())
    {}

    /**
     * 获取容器
     */
    public function container()
    {
        return $this->_container;
    }

    /**
     * 检测组件是否存在
     * @param $key
     * @return bool
     */
    public function hasService($key)
    {
        return $this->_container->has($key);
    }

    /**
     * 获取服务组件
     * @param $key
     * @return mixed
     */
    public function getService($key)
    {
        if (!empty($this->_services[$key])) {
            return $this->_services[$key];
        }

        if ($this->_container && $this->_container->has($key)) {
            $instance = $this->_container->create($key);
            if ($instance) {
                $this->setService($key, $instance);
                return $instance;
            }
        }

        $container = Ocara::container();
        if ($container->has($key)) {
            $instance = $container->create($key);
            if ($instance) {
                $this->setService($key, $instance);
                return $instance;
            }
        }

        return null;
    }

    /**
     * 设置服务组件
     * @param $key
     * @param $service
     */
    public function setService($key, $service)
    {
        $this->_services[$key] = $service;
    }

    /**
     * 获取不存在的属性时
     * @param string $key
     * @return array|null
     */
    public function &__get($key)
    {
        if ($this->hasProperty($key)) {
            return $this->getProperty($key);
        }

        if ($instance = $this->getService($key)) {
            return $instance;
        }

        Error::show('no_service', array($key));
    }
}