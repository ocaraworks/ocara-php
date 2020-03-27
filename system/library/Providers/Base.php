<?php
/**
 * 服务提供器基类
 * @Copyright (c) http://www.ocara.cn and http://www.ocaraframework.com All rights reserved.
 * @author Lin YiHu <linyhtianwa@163.com>
 */

namespace Ocara\Providers;

use Ocara\Core\ServiceProvider;

class Base extends ServiceProvider
{
    /**
     * 注册服务组件
     */
    public function register()
    {
        $this->container->bindSingleton(ocConfig('SYSTEM_SINGLETON_SERVICE_BINDS'));
        $this->container->bind(ocConfig('SYSTEM_SERVICE_BINDS'));
    }
}