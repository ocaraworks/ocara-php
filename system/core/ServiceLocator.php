<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架 服务提供器类Provider
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara;

class ServiceLocator extends Base
{
    protected $_plugin;
    protected $_services;

    public function __construct()
    {
        $this->_plugin = new Container();
        $this->_register();
        $this->_init();
    }

    public function _register()
    {}

    public function _init()
    {}

    /**
     * 获取不存在的属性时
     * @param string $key
     * @return array|null
     */
    public function &__get($key)
    {
        if ($this->hasProperty($key)) {
            $value = &$this->getProperty($key);
            return $value;
        }

        if (isset($this->_services[$key])) {
            return $this->_services[$key];
        }

        if ($this->_plugin) {
            $instance = $this->_plugin->get($key);
            $this->setProperty($key, $instance);
            return $this->_services[$key] = $instance;
        }

        Error::show('no_property', array($key));
    }
}