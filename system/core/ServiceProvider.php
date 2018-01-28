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
    public function has($key)
    {
        return $this->_container->has($key);
    }

    /**
     * 获取服务组件
     * @param $key
     * @return mixed
     */
    public function get($key)
    {
        if ($this->has($key)) {
            return $this->get($key);
        }

        $instance = $this->create($key);
        if ($instance) {
            return $this->set($key, $instance);
        }

        return null;
    }

    /**
     * 新建服务组件
     * @param mixed $key
     * @param array $params
     * @param array $deps
     * @return mixed|null
     */
    public function create($key, $params = array(), $deps = array())
    {
        $instance = null;
        if ($this->_container && $this->_container->has($key)) {
            $instance = $this->_container->create($key, $params, $deps);
        }

        $container = Ocara::container();
        if ($container->has($key)) {
            $instance = $container->create($key, $params, $deps);
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