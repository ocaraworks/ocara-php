<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架 默认服务提供器Main
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara\Providers;

use Ocara\Ocara;
use Ocara\ServiceProvider;

class Main extends ServiceProvider
{
    /**
     * 注册服务组件
     */
    public function register()
    {
        $this->_createService(ocConfig('SYSTEM_SINGLETON_SERVICE_CLASS'), 'bindSingleton');
        $this->_createService(ocConfig('SYSTEM_SERVICE_CLASS'), 'bind');
    }

    /**
     * 新建服务组件
     * @param $services
     * @param $method
     */
    protected function _createService($services, $method)
    {
        foreach ($services as $name => $namespace) {
            $this->_container->$method($name, function() use($namespace) {
                $args = func_get_args();
                if (method_exists($namespace, 'getInstance')) {
                    return call_user_func_array(array($namespace, 'getInstance'), $args);
                } else {
                    return ocClass($namespace, $args);
                }
            });
        }
    }
}