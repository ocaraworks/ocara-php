<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架   控制器特性基类FeatureBase
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara\Controllers\Feature;

use Ocara\Core\Base as ClassBase;

defined('OC_PATH') or exit('Forbidden!');

class Base extends ClassBase
{
    /**
     * 获取路由
     * @param $module
     * @param $controller
     * @param array $get
     * @return array|mixed
     */
    public function getRoute($module, $controller, array $get)
    {
        $action = array_shift($get);
        $route = array($module, $controller, $action);

        if (ocService()->url->isVirtualUrl(OC_URL_ROUTE_TYPE)) {
            $_GET = array_values($get);
        } else {
            $_GET = $get;
        }

        return $route;
    }
}