<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架 服务组件基类Component
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara;

class ServiceProvider extends Base
{
    protected $_container;

    public function __construct()
    {
        $this->_container = new Container();
    }

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
        if ($this->_container->isBound($key)) {
            $instance = $this->_container->get($key);
            $this->setProperty($key, $instance);
            return $instance;
        }
        Error::show('no_property', array($key));
    }

    /**
     * 调用未定义的方法时
     * @param string $name
     * @param array $params
     * @return mixed
     * @throws Exception\Exception
     */
    public function __call($name, $params)
    {
        if (is_object($this->_container) && method_exists($this->_container, $name)) {
            return call_user_func_array(array(&$this->_container, $name), $params);
        }
        Error::show('no_method', array($name));
    }
}