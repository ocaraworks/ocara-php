<?php
/*************************************************************************************************
 * -----------------------------------------------------------------------------------------------
 * Ocara开源框架   路由处理类Route
 * Copyright (c) http://www.ocara.cn All rights reserved.
 * -----------------------------------------------------------------------------------------------
 * @author Lin YiHu <linyhtianwa@163.com>
 ************************************************************************************************/
namespace Ocara\Core;

use Ocara\Core\Base;
use Ocara\Core\ControllerBase;

defined('OC_PATH') or exit('Forbidden!');

class Route extends Base
{
    /**
     * 解析路由
     */
    public function parseRouteInfo()
    {
        $get = $_GET;
        $moduleClass = OC_EMPTY;
        $controller = OC_EMPTY;

        $service = ocService();
        $module = array_shift($get);
        $uModule = ucfirst($module);

        if ($uModule) {
            $moduleNamespace = OC_MODULE_NAMESPACE ? ocNamespace(OC_MODULE_NAMESPACE): 'app\modules\\';
            $moduleClass = sprintf($moduleNamespace .'%s\controller\%sModule', $module, ucfirst($module));
            if (ocClassExists($moduleClass)) {
                $controller = $get ? array_shift($get) : null;
            } else {
                $controller = $module;
                $module = null;
            }
        }

        if (empty($module)) {
            $moduleClass = 'app\controller\\' . 'Module';
        }

        $controllerType = $moduleClass::controllerType();
        $featureClass = ControllerBase::getFeatureClass($controllerType);

        if (!ocClassExists($featureClass)) {
            $service->error->show('not_exists_class', array($featureClass));
        }

        $feature = new $featureClass();
        $route = $feature->getRoute($module, $controller, $get);

        if ($service->url->isVirtualUrl(OC_URL_ROUTE_TYPE)) {
            $_GET = $this->formatGet($_GET);
        }

        return $route;
    }

    /**
     * 格式化GET参数
     * @param array $data
     * @return array
     */
    public function formatGet(array $data)
    {
        $last = $get = array();
        if (is_array(end($data))) {
            $last = array_pop($data);
        }
        
        ksort($data);
        $data = array_chunk($data, 2);

        foreach($data as $row) {
            if ($row[0]) {
                $get[$row[0]] = isset($row[1]) ? $row[1] : null;
            }
        }

        return $last ? $get + $last : $get;
    }
}