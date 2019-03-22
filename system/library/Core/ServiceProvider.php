<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架 服务提供器类Provider
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara\Core;

use Ocara\Core\Base;
use Ocara\Core\Container;
use Ocara\Exceptions\Exception;
use Ocara\Interfaces\ServiceProvider as ServiceProviderInterface;

class ServiceProvider extends Base implements ServiceProviderInterface
{
    protected $_container;
    protected $_services = array();

    private static $_default;

    /**
     * 初始化
     * ServiceProvider constructor.
     * @param array $data
     * @param \Ocara\Core\Container|null $container
     */
    public function __construct(array $data = array(), Container $container = null)
    {
        $this->_setProperty($data);
        $this->setContainer($container ? : new Container());
        $this->register();
        $this->init();
    }

    /**
     * 设置默认服务提供器
     * @param ServiceProvider $provider
     */
    public static function setDefault(ServiceProvider $provider)
    {
        if (self::$_default === null) {
            self::$_default = $provider;
        }
    }

    /**
     * 获取默认服务提供器
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
     * 注册服务组件
     */
    public function register()
    {}

    /**
     * 初始化
     */
    public function init()
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
     * 检测是否可提供服务组件
     * @param $name
     * @return bool
     */
    public function canService($name)
    {
        return array_key_exists($name, $this->_services)
            || $this->_container->has($name)
            || ocContainer()->has($name);
    }

    /**
     * 获取服务组件，如果没有就加载和新建
     * @param string $name
     * @param array $params
     * @param array $deps
     * @return mixed|null
     */
    public function loadService($name, $params = array(), $deps = array())
    {
        $instance = $this->getService($name);

        if (empty($instance)) {
            if ($this->_container && $this->_container->hasBindAll($name)) {
                $instance = $this->_container->get($name, $params, $deps);
                $this->setService($name, $instance);
            } elseif (ocContainer()->hasBindAll($name)) {
                $instance = ocContainer()->get($name, $params, $deps);
                $this->setService($name, $instance);
            }
        }

        return $instance;
    }

    /**
     * 新建动态服务组件
     * @param string $name
     * @param array $params
     * @param array $deps
     * @return mixed
     * @throws Exception
     */
    public function createService($name, $params = array(), $deps = array())
    {
        if ($this->_container && $this->_container->has($name)) {
            return $this->_container->create($name, $params, $deps);
        } elseif (ocContainer()->hasBind($name)) {
            return ocContainer()->create($name, $params, $deps);
        } else {
            throw new Exception('no_service', array($name));
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
     * 检测服务组件是否存在
     * @param $name
     * @return bool
     */
    public function hasService($name)
    {
        return array_key_exists($name, $this->_services);
    }

    /**
     * 获取已注册服务组件
     * @param $name
     * @return mixed|null
     */
    public function getService($name)
    {
        return array_key_exists($name, $this->_services) ? $this->_services[$name] : null;
    }

    /**
     * 属性不存在时的处理
     * @param string $key
     * @return mixed|null
     */
    public function __none($key)
    {
        $instance = $this->loadService($key);
        if ($instance) {
            return $instance;
        }

        ocService()->error->show('no_service', array($key));
    }
}