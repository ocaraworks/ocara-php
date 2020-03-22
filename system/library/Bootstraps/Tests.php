<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架 单元测试启动类
 * @Copyright (c) http://www.ocara.cn and http://www.ocaraframework.com All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/

namespace Ocara\Bootstraps;

use Ocara\Exceptions\Exception;
use Ocara\Interfaces\Bootstrap as BootstrapInterface;
use Ocara\Core\BootstrapBase;

class Tests extends BootstrapBase implements BootstrapInterface
{

    /**
     * 开始执行
     * @param array $route
     * @return mixed|void
     */
    public function start($route = array())
    {
        $service = ocService();

        if (!empty($route['module'])) {
            $service->config->loadModuleConfig($route);
            $service->lang->loadModuleConfig($route);
        }
    }
}