<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架 服务提供器类Provider
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara;

use Ocara\Base;
use Ocara\Container;
use Ocara\Exceptions\Exception;
use Ocara\Interfaces\ServiceProvider as ServiceProviderInterface;

class ServiceProvider extends Base implements ServiceProviderInterface
{
    protected $_container;
    protected $_services = array();

    /**
     * 初始化
     * ServiceProvider constructor.
     * @param array $data
     */
    public function __construct(array $data = array())
    {
        $this->setContainer(new Container());
        $this->setProperty($data);
        $this->register();
        $this->boot();
    }

    /**
     * 注册服务组件
     */
    public function register()
    {}

    /**
     * 启动服务
     */
    public function boot()
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
    public function hasService($name)
    {
        return array_key_exists($name, $this->_services)
            || $this->_container->has($name)
            || Container::getDefault()->has($name);
    }

    /**
     * 获取服务组件
     * @param string $name
     * @return array|mixed|null
     */
    public function getService($name, $params = array(), $deps = array())
    {
        $instance = null;

        if (array_key_exists($name, $this->_services)) {
            $instance = $this->_services[$name];
        } elseif ($this->_container && $this->_container->hasBindAll($key)) {
            $instance = $this->_container->get($key, $params, $deps);
            $this->setService($key, $instance);
        } elseif (Container::getDefault()->hasBindAll($key)) {
            $instance = Container::getDefault()->get($key, $params, $deps);
            $this->setService($key, $instance);
        }

        return $instance;
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
        if ($this->hasService($key)) {
            throw new Exception('exists_service', array($key));
        }

        if ($this->_container && $this->_container->hasBind($key)) {
            return $this->_container->create($key, $params, $deps);
        } elseif (Container::getDefault()->hasBind($key)) {
            return Container::getDefault()->create($key, $params, $deps);
        } else {
            throw new Exception('no_service', array($key));
        }
    }

    /**
     * 动态设置实例
     * @param $name
     * @param $service
     */
    public function setService($name, $service)
    {
        $this->_services[$name] = $service;
    }

    /**
     * 属性不存在时的处理
     * @param string $key
     * @throws Exception\Exception
     */
    public function __none($key)
    {
        $instance = $this->getService($key);
        if ($instance) {
            return $instance;
        }

        Ocara::services()->error->show('no_service', array($key));
    }
}