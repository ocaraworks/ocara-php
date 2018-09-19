<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架   控制器特性基类FeatureBase
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara\Controllers\Feature;

use Ocara\Ocara;
use Ocara\Route;
use Ocara\Base as ClassBase;

defined('OC_PATH') or exit('Forbidden!');

class Base extends ClassBase
{
    /**
     * 设置最终路由
     * @param string $module
     * @param string $controller
     * @param string $action
     * @return array
     */
    public function getLastRoute($module, $controller, $action)
    {
        if (Ocara::services()->url->isVirtualUrl(OC_URL_ROUTE_TYPE)) {
            $_GET = Route::formatGet($_GET);
        }

        if (empty($action)) {
            $action = ocConfig('DEFAULT_ACTION', 'index');
        }

        return array($module, $controller, $action);
    }
}