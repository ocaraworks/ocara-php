<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架 服务提供器类Provider
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara;

use Ocara\Interfaces\ServiceManager as ServiceManagerInterface;

class ServiceManager extends Base implements ServiceManagerInterface
{
    protected $_services;

    /**
     * 初始化
     * ServiceManager constructor.
     */
    public function __construct()
    {
        $this->_plugin = new Container();
        $this->register();
    }

    /**
     * 注册服务组件
     */
    public function register()
    {}

    /**
     * 检测组件是否存在
     * @param $key
     * @return bool
     */
    public function hasService($key)
    {
        return $this->_plugin->has($key);
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

        if ($this->_plugin) {
            if ($instance = $this->_plugin->create($key)) {
                $this->setService($key, $instance);
                return $instance;
            }
        } else {
            $container = Ocara::container();
            if ($instance = $container->create($key)) {
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
        if ($instance = $this->getService($key)) {
            return $instance;
        }

        Error::show('no_service', array($key));
    }
}